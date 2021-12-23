<?php

namespace Appwrite\Resque;

use Utopia\Cache\Cache;
use Utopia\Cache\Adapter\Redis as RedisCache;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Database\Adapter\MariaDB;

abstract class Worker
{
    public array $args = [];

    abstract public function init(): void;

    abstract public function run(): void;

    abstract public function shutdown(): void;

    const MAX_ATTEMPTS = 10;
    const SLEEP_TIME = 2;

    const DATABASE_INTERNAL = 'internal';
    const DATABASE_EXTERNAL = 'external';
    const DATABASE_CONSOLE = 'console';

    public function setUp(): void
    {
        $this->init();
    }

    public function perform(): void
    {
        $this->run();
    }

    public function tearDown(): void
    {
        $this->shutdown();
    }
    /**
     * Get internal project database
     * @param string $projectId
     * @return Database
     */
    protected function getInternalDB(string $projectId): Database
    {
        return $this->getDB(self::DATABASE_INTERNAL, $projectId);
    }

    /**
     * Get external project database
     * @param string $projectId
     * @return Database
     */
    protected function getExternalDB(string $projectId): Database
    {
        return $this->getDB(self::DATABASE_EXTERNAL, $projectId);
    }

    /**
     * Get console database
     * @return Database
     */
    protected function getConsoleDB(): Database
    {
        return $this->getDB(self::DATABASE_CONSOLE);
    }

    /**
     * Get console database
     * @param string $type One of (internal, external, console)
     * @param string $projectId of internal or external DB
     * @return Database
     */
    private function getDB($type, $projectId = ''): Database
    {
        global $register;

        $namespace = '';
        $sleep = self::SLEEP_TIME; // overwritten when necessary

        switch ($type) {
            case self::DATABASE_INTERNAL:
                if (!$projectId) {
                    throw new \Exception('ProjectID not provided - cannot get database');
                }
                $namespace = "project_{$projectId}";
                break;
            case self::DATABASE_EXTERNAL:
                if (!$projectId) {
                    throw new \Exception('ProjectID not provided - cannot get database');
                }
                $namespace = "project_{$projectId}";
                break;
            case self::DATABASE_CONSOLE:
                $namespace = "_console";
                $sleep = 5; // ConsoleDB needs extra sleep time to ensure tables are created
                break;
            default:
                throw new \Exception('Unknown database type: ' . $type);
                break;
        }

        $attempts = 0;

        do {
            try {
                $attempts++;
                $cache = new Cache(new RedisCache($register->get('cache')));
                $database = new Database(new MariaDB($register->get('db')), $cache);
                $database->setDefaultDatabase('appwrite');
                $database->setNamespace($namespace); // Main DB
                if (!empty($projectId) && !$database->getDocument('projects', $projectId)->isEmpty()) {
                    throw new \Exception("Project does not exist: {$projectId}");
                }
                break; // leave loop if successful
            } catch(\Exception $e) {
                Console::warning("Database not ready. Retrying connection ({$attempts})...");
                if ($attempts >= self::MAX_ATTEMPTS) {
                    throw new \Exception('Failed to connect to database: '. $e->getMessage());
                }
                sleep($sleep);
            }
        } while ($attempts < self::MAX_ATTEMPTS);

        return $database;
    }
}
