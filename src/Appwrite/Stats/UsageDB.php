<?php

namespace Appwrite\Stats;

use Utopia\Database\Database;
use Utopia\Database\Document;

class UsageDB extends Usage
{
    public function __construct(Database $database, callable $errorHandler = null)
    {
        $this->database = $database;
        $this->errorHandler = $errorHandler;
    }
    /**
     * Create or Update Mertic
     * Create or update each metric in the stats collection for the given project
     *
     * @param string $projectId
     * @param string $metric
     * @param int $value
     *
     * @return void
     */
    private function createOrUpdateMetric(string $projectId, string $metric, int $value): void
    {
        foreach ($this->periods as $options) {
            $period = $options['key'];
            $time = (int) (floor(time() / $options['multiplier']) * $options['multiplier']);
            $id = \md5("{$time}_{$period}_{$metric}");
            $this->database->setNamespace('_' . $projectId);
            try {
                $document = $this->database->getDocument('stats', $id);
                if ($document->isEmpty()) {
                    $this->database->createDocument('stats', new Document([
                        '$id' => $id,
                        'period' => $period,
                        'time' => $time,
                        'metric' => $metric,
                        'value' => $value,
                        'type' => 1,
                    ]));
                } else {
                    $this->database->updateDocument(
                        'stats',
                        $document->getId(),
                        $document->setAttribute('value', $value)
                    );
                }
            } catch (\Exception$e) { // if projects are deleted this might fail
                if (is_callable($this->errorHandler)) {
                    call_user_func($this->errorHandler, "Unable to save data for project {$projectId} and metric {$metric}: {$e->getMessage()}", $e->getTraceAsString());
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Foreach Document
     * Call provided callback for each document in the collection
     *
     * @param string $projectId
     * @param string $collection
     * @param array $queries
     * @param callable $callback
     *
     * @return void
     */
    private function foreachDocument(string $projectId, string $collection, array $queries, callable $callback): void
    {
        $limit = 50;
        $results = [];
        $sum = $limit;
        $latestDocument = null;
        $this->database->setNamespace('_' . $projectId);

        while ($sum === $limit) {
            $results = $this->database->find($collection, $queries, $limit, cursor:$latestDocument);
            if (empty($results)) {
                return;
            }

            $sum = count($results);

            foreach ($results as $document) {
                if (is_callable($callback)) {
                    $callback($document);
                }
            }
            $latestDocument = $results[array_key_last($results)];
        }
    }

    /**
     * Sum
     * Calculate sum of a attribute of documents in collection
     *
     * @param string $projectId
     * @param string $collection
     * @param string $attribute
     * @param string $metric
     *
     * @return int
     */
    private function sum(string $projectId, string $collection, string $attribute, string $metric): int
    {
        $this->database->setNamespace('_' . $projectId);
        $sum = (int) $this->database->sum($collection, $attribute);
        $this->createOrUpdateMetric($projectId, $metric, $sum);
        return $sum;
    }

    /**
     * Count
     * Count number of documents in collection
     *
     * @param string $projectId
     * @param string $collection
     * @param string $metric
     *
     * @return int
     */
    private function count(string $projectId, string $collection, string $metric): int
    {
        $this->database->setNamespace("_{$projectId}");
        $count = $this->database->count($collection);

        $this->createOrUpdateMetric($projectId, $metric, $count);
        return $count;
    }

    /**
     * Deployments Total
     * Total sum of storage used by deployments
     *
     * @param string $projectId
     *
     * @return int
     */
    private function deploymentsTotal(string $projectId): int
    {
        return $this->sum($projectId, 'deployments', 'size', 'stroage.deployments.total');
    }

    /**
     * Users Stats
     * Metric: users.count
     *
     * @param string $projectId
     *
     * @return void
     */
    private function usersStats(string $projectId): void
    {
        $this->count($projectId, 'users', 'users.count');
    }

    /**
     * Storage Stats
     * Metrics: storage.total, storage.files.total, storage.buckets.{bucketId}.files.total,
     * storage.buckets.count, storage.files.count, storage.buckets.{bucketId}.files.count
     *
     * @param string $projectId
     *
     * @return void
     */
    private function storageStats(string $projectId): void
    {
        $deploymentsTotal = $this->deploymentsTotal($projectId);

        $projectFilesTotal = 0;
        $projectFilesCount = 0;

        $metric = 'storage.buckets.count';
        $this->count($projectId, 'buckets', $metric);

        $this->foreachDocument($projectId, 'buckets', [], function ($bucket) use (&$projectFilesCount, &$projectFilesTotal, $projectId,) {
            $metric = "storage.buckets.{$bucket->getId()}.files.count";

            $count = $this->count($projectId, 'bucket_' . $bucket->getInternalId(), $metric);
            $projectFilesCount += $count;

            $metric = "storage.buckets.{$bucket->getId()}.files.total";
            $sum = $this->sum($projectId, 'bucket_' . $bucket->getInternalId(), 'sizeOriginal', $metric);
            $projectFilesTotal += $sum;
        });

        $this->createOrUpdateMetric($projectId, 'storage.files.count', $projectFilesCount);
        $this->createOrUpdateMetric($projectId, 'storage.files.total', $projectFilesTotal);

        $this->createOrUpdateMetric($projectId, 'storage.total', $projectFilesTotal + $deploymentsTotal);
    }

    /**
     * Database Stats
     * Collect all database stats
     * Metrics: database.collections.count, database.collections.{collectionId}.documents.count,
     * database.documents.count
     *
     * @param string $projectId
     *
     * @return void
     */
    private function databaseStats(string $projectId): void
    {
        $projectDocumentsCount = 0;

        $metric = 'database.collections.count';
        $this->count($projectId, 'collections', $metric);

        $this->foreachDocument($projectId, 'collections', [], function ($collection) use (&$projectDocumentsCount, $projectId,) {
            $metric = "database.collections.{$collection->getId()}.documents.count";

            $count = $this->count($projectId, 'collection_' . $collection->getInternalId(), $metric);
            $projectDocumentsCount += $count;
        });

        $this->createOrUpdateMetric($projectId, 'database.documents.count', $projectDocumentsCount);
    }

    /**
     * Collect Stats
     * Collect all database related stats
     *
     * @return void
     */
    public function collect(): void
    {
        $this->foreachDocument('console', 'projects', [], function ($project) {
                $projectId = $project->getId();
                $this->usersStats($projectId);
                $this->databaseStats($projectId);
                $this->storageStats($projectId);
        });
    }
}
