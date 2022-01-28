<?php

use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Appwrite\Resque\Worker;
use Utopia\Storage\Device\Local;
use Utopia\Abuse\Abuse;
use Utopia\Abuse\Adapters\TimeLimit;
use Utopia\CLI\Console;
use Utopia\Audit\Audit;

require_once __DIR__ . '/../init.php';

Console::title('Deletes V1 Worker');
Console::success(APP_NAME . ' deletes worker v1 has started' . "\n");

class DeletesV1 extends Worker
{
    /**
     * @var Database
     */
    protected $consoleDB = null;

    public function getName(): string {
        return "deletes";
    }

    public function init(): void
    {
    }

    public function run(): void
    {
        $projectId = $this->args['projectId'] ?? '';
        $type = $this->args['type'] ?? '';

        switch (strval($type)) {
            case DELETE_TYPE_DOCUMENT:
                $document = new Document($this->args['document'] ?? []);

                switch ($document->getCollection()) {
                    case DELETE_TYPE_COLLECTIONS:
                        $this->deleteCollection($document, $projectId);
                        break;
                    case DELETE_TYPE_PROJECTS:
                        $this->deleteProject($document);
                        break;
                    case DELETE_TYPE_FUNCTIONS:
                        $this->deleteFunction($document, $projectId);
                        break;
                    case DELETE_TYPE_DEPLOYMENTS:
                        $this->deleteDeployment($document, $projectId);
                        break;
                    case DELETE_TYPE_USERS:
                        $this->deleteUser($document, $projectId);
                        break;
                    case DELETE_TYPE_TEAMS:
                        $this->deleteMemberships($document, $projectId);
                        break;
                    default:
                        Console::error('No lazy delete operation available for document of type: ' . $document->getCollection());
                        break;
                }
                break;

            case DELETE_TYPE_EXECUTIONS:
                $this->deleteExecutionLogs($this->args['timestamp']);
                break;

            case DELETE_TYPE_AUDIT:
                $timestamp = $this->args['timestamp'] ?? 0;
                $document = new Document($this->args['document'] ?? []);

                if (!empty($timestamp)) {
                    $this->deleteAuditLogs($this->args['timestamp']);
                }

                if (!$document->isEmpty()) {
                    $this->deleteAuditLogsByResource('document/' . $document->getId(), $projectId);
                }

                break;

            case DELETE_TYPE_ABUSE:
                $this->deleteAbuseLogs($this->args['timestamp']);
                break;

            case DELETE_TYPE_REALTIME:
                $this->deleteRealtimeUsage($this->args['timestamp']);
                break;

            case DELETE_TYPE_CERTIFICATES:
                $document = new Document($this->args['document']);
                $this->deleteCertificates($document);
                break;

            case DELETE_TYPE_USAGE:
                $this->deleteUsageStats($this->args['timestamp1d'], $this->args['timestamp30m']);
                break;
            default:
                Console::error('No delete operation for type: ' . $type);
                break;
        }
    }

    public function shutdown(): void
    {
    }

    /**
     * @param Document $document teams document
     * @param string $projectId
     */
    protected function deleteCollection(Document $document, string $projectId): void
    {
        $collectionId = $document->getId();

        $dbForProject = $this->getProjectDB($projectId);

        $dbForProject->deleteCollection('collection_' . $collectionId);

        $this->deleteByGroup('attributes', [
            new Query('collectionId', Query::TYPE_EQUAL, [$collectionId])
        ], $dbForProject);

        $this->deleteByGroup('indexes', [
            new Query('collectionId', Query::TYPE_EQUAL, [$collectionId])
        ], $dbForProject);

        $this->deleteAuditLogsByResource('collection/' . $collectionId, $projectId);
    }

    /**
     * @param int $timestamp1d
     * @param int $timestamp30m
     */
    protected function deleteUsageStats(int $timestamp1d, int $timestamp30m)
    {
        $this->deleteForProjectIds(function (string $projectId) use ($timestamp1d, $timestamp30m) {
            $dbForProject = $this->getProjectDB($projectId);
            // Delete Usage stats
            $this->deleteByGroup('stats', [
                new Query('time', Query::TYPE_LESSER, [$timestamp1d]),
                new Query('period', Query::TYPE_EQUAL, ['1d']),
            ], $dbForProject);

            $this->deleteByGroup('stats', [
                new Query('time', Query::TYPE_LESSER, [$timestamp30m]),
                new Query('period', Query::TYPE_EQUAL, ['30m']),
            ], $dbForProject);
        });
    }

    /**
     * @param Document $document teams document
     * @param string $projectId
     */
    protected function deleteMemberships(Document $document, string $projectId): void
    {
        $teamId = $document->getAttribute('teamId', '');

        // Delete Memberships
        $this->deleteByGroup('memberships', [
            new Query('teamId', Query::TYPE_EQUAL, [$teamId])
        ], $this->getProjectDB($projectId));
    }

    /**
     * @param Document $document project document
     */
    protected function deleteProject(Document $document): void
    {
        $projectId = $document->getId();

        // Delete all DBs
        $this->getProjectDB($projectId)->delete($projectId);

        // Delete all storage directories
        $uploads = new Local(APP_STORAGE_UPLOADS . '/app-' . $document->getId());
        $cache = new Local(APP_STORAGE_CACHE . '/app-' . $document->getId());

        $uploads->delete($uploads->getRoot(), true);
        $cache->delete($cache->getRoot(), true);
    }

    /**
     * @param Document $document user document
     * @param string $projectId
     */
    protected function deleteUser(Document $document, string $projectId): void
    {
        /**
         * DO NOT DELETE THE USER RECORD ITSELF. 
         * WE RETAIN THE USER RECORD TO RESERVE THE USER ID AND ENSURE THAT THE USER ID IS NOT REUSED.
         */
        
        $userId = $document->getId();
        $user = $this->getProjectDB($projectId)->getDocument('users', $userId);

        // Delete all sessions of this user from the sessions table and update the sessions field of the user record
        $this->deleteByGroup('sessions', [
            new Query('userId', Query::TYPE_EQUAL, [$userId])
        ], $this->getProjectDB($projectId));
        
        $user->setAttribute('sessions', []);
        $updated = Authorization::skip(fn() => $this->getProjectDB($projectId)->updateDocument('users', $userId, $user));

        // Delete Memberships and decrement team membership counts
        $this->deleteByGroup('memberships', [
            new Query('userId', Query::TYPE_EQUAL, [$userId])
        ], $this->getProjectDB($projectId), function (Document $document) use ($projectId) {

            if ($document->getAttribute('confirm')) { // Count only confirmed members
                $teamId = $document->getAttribute('teamId');
                $team = $this->getProjectDB($projectId)->getDocument('teams', $teamId);
                if (!$team->isEmpty()) {
                    $team = $this->getProjectDB($projectId)->updateDocument('teams', $teamId, new Document(\array_merge($team->getArrayCopy(), [
                        'sum' => \max($team->getAttribute('sum', 0) - 1, 0), // Ensure that sum >= 0
                    ])));
                }
            }
        });
    }

    /**
     * @param int $timestamp
     */
    protected function deleteExecutionLogs(int $timestamp): void
    {
        $this->deleteForProjectIds(function (string $projectId) use ($timestamp) {
            $dbForProject = $this->getProjectDB($projectId);
            // Delete Executions
            $this->deleteByGroup('executions', [
                new Query('dateCreated', Query::TYPE_LESSER, [$timestamp])
            ], $dbForProject);
        });
    }

    /**
     * @param int $timestamp
     */
    protected function deleteRealtimeUsage(int $timestamp): void
    {
        $this->deleteForProjectIds(function (string $projectId) use ($timestamp) {
            $dbForProject = $this->getProjectDB($projectId);
            // Delete Dead Realtime Logs
            $this->deleteByGroup('realtime', [
                new Query('timestamp', Query::TYPE_LESSER, [$timestamp])
            ], $dbForProject);
        });
    }

    /**
     * @param int $timestamp
     */
    protected function deleteAbuseLogs(int $timestamp): void
    {
        if ($timestamp == 0) {
            throw new Exception('Failed to delete audit logs. No timestamp provided');
        }

        $this->deleteForProjectIds(function (string $projectId) use ($timestamp) {
            $dbForProject = $this->getProjectDB($projectId);
            $timeLimit = new TimeLimit("", 0, 1, $dbForProject);
            $abuse = new Abuse($timeLimit);

            $status = $abuse->cleanup($timestamp);
            if (!$status) {
                throw new Exception('Failed to delete Abuse logs for project ' . $projectId);
            }
        });
    }

    /**
     * @param int $timestamp
     */
    protected function deleteAuditLogs(int $timestamp): void
    {
        if ($timestamp == 0) {
            throw new Exception('Failed to delete audit logs. No timestamp provided');
        }
        $this->deleteForProjectIds(function (string $projectId) use ($timestamp) {
            $dbForProject = $this->getProjectDB($projectId);
            $audit = new Audit($dbForProject);
            $status = $audit->cleanup($timestamp);
            if (!$status) {
                throw new Exception('Failed to delete Audit logs for project' . $projectId);
            }
        });
    }

    /**
     * @param int $timestamp
     */
    protected function deleteAuditLogsByResource(string $resource, string $projectId): void
    {
        $dbForProject = $this->getProjectDB($projectId);

        $this->deleteByGroup(Audit::COLLECTION, [
            new Query('resource', Query::TYPE_EQUAL, [$resource])
        ], $dbForProject);
    }

    /**
     * @param Document $document function document
     * @param string $projectId
     */
    protected function deleteFunction(Document $document, string $projectId): void
    {
        $dbForProject = $this->getProjectDB($projectId);
        $device = new Local(APP_STORAGE_FUNCTIONS . '/app-' . $projectId);
        $deploymentIds = [];
        
        // Delete Deployments
        $this->deleteByGroup('deployments', [
            new Query('functionId', Query::TYPE_EQUAL, [$document->getId()])
        ], $dbForProject, function (Document $document) use ($device, &$deploymentIds) {
            $deploymentIds[] = $document->getId();
            if ($device->delete($document->getAttribute('path', ''), true)) {
                Console::success('Delete deployment files: ' . $document->getAttribute('path', ''));
            } else {
                Console::error('Failed to delete deployment files: ' . $document->getAttribute('path', ''));
            }
        });

        // Delete builds
        $this->deleteByGroup('builds', [
            new Query('deploymentId', Query::TYPE_EQUAL, $deploymentIds)
        ], $dbForProject, function (Document $document) use ($device) {
            if ($device->delete($document->getAttribute('outputPath', ''), true)) {
                Console::success('Deleted build files: ' . $document->getAttribute('outputPath', ''));
            } else {
                Console::error('Failed to delete build files: ' . $document->getAttribute('outputPath', ''));
            }
        });

        // Delete Executions
        $this->deleteByGroup('executions', [
            new Query('functionId', Query::TYPE_EQUAL, [$document->getId()])
        ], $dbForProject);

    }


    /**
     * @param Document $document to be deleted
     * @param Database $database to delete it from
     * @param callable $callback to perform after document is deleted
     *
     * @return bool
     */
    protected function deleteById(Document $document, Database $database, callable $callback = null): bool
    {
        Authorization::disable();

        if ($database->deleteDocument($document->getCollection(), $document->getId())) {
            Console::success('Deleted document "' . $document->getId() . '" successfully');

            if (is_callable($callback)) {
                $callback($document);
            }

            return true;
        } else {
            Console::error('Failed to delete document: ' . $document->getId());
            return false;
        }

        Authorization::reset();
    }

    /**
     * @param callable $callback
     */
    protected function deleteForProjectIds(callable $callback): void
    {
        $count = 0;
        $chunk = 0;
        $limit = 50;
        $projects = [];
        $sum = $limit;

        $executionStart = \microtime(true);

        while ($sum === $limit) {
            $projects = Authorization::skip(fn() => $this->getConsoleDB()->find('projects', [], $limit, ($chunk * $limit)));

            $chunk++;

            /** @var string[] $projectIds */
            $projectIds = array_map(fn(Document $project) => $project->getId(), $projects);

            $sum = count($projects);

            Console::info('Executing delete function for chunk #' . $chunk . '. Found ' . $sum . ' projects');
            foreach ($projectIds as $projectId) {
                $callback($projectId);
                $count++;
            }
        }

        $executionEnd = \microtime(true);
        Console::info("Found {$count} projects " . ($executionEnd - $executionStart) . " seconds");
    }

    /**
     * @param string $collection collectionID
     * @param Query[] $queries
     * @param Database $database
     * @param callable $callback
     */
    protected function deleteByGroup(string $collection, array $queries, Database $database, callable $callback = null): void
    {
        $count = 0;
        $chunk = 0;
        $limit = 50;
        $results = [];
        $sum = $limit;

        $executionStart = \microtime(true);

        while ($sum === $limit) {
            $chunk++;

            $results = Authorization::skip(fn() => $database->find($collection, $queries, $limit, 0));

            $sum = count($results);

            Console::info('Deleting chunk #' . $chunk . '. Found ' . $sum . ' documents');

            foreach ($results as $document) {
                $this->deleteById($document, $database, $callback);
                $count++;
            }
        }

        $executionEnd = \microtime(true);

        Console::info("Deleted {$count} document by group in " . ($executionEnd - $executionStart) . " seconds");
    }

    /**
     * @param Document $document certificates document 
     */
    protected function deleteCertificates(Document $document): void
    {
        $domain = $document->getAttribute('domain');
        $directory = APP_STORAGE_CERTIFICATES . '/' . $domain;
        $checkTraversal = realpath($directory) === $directory;

        if ($domain && $checkTraversal && is_dir($directory)) {
            array_map('unlink', glob($directory . '/*.*'));
            rmdir($directory);
            Console::info("Deleted certificate files for {$domain}");
        } else {
            Console::info("No certificate files found for {$domain}");
        }
    }
}
