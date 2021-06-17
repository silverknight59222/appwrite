<?php

use Appwrite\Database\Validator\Authorization;
use Appwrite\Resque\Worker;
use Utopia\Abuse\Abuse;
use Utopia\Abuse\Adapters\TimeLimit;
use Utopia\Audit\Audit;
use Utopia\Cache\Cache;
use Utopia\Cache\Adapter\Redis as RedisCache;
use Utopia\CLI\Console;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Adapter\MariaDB;
use Utopia\Storage\Device\Local;

require_once __DIR__.'/../init.php';

Console::title('Database V1 Worker');
Console::success(APP_NAME.' database worker v1 has started'."\n");

class DatabaseV1 extends Worker
{
    public $args = [];

    public function init(): void
    {
    }

    public function run(): void
    {
        $collections = Config::getParam('collections2');

        var_dump($collections);

    }

    public function shutdown(): void
    {
    }

    /**
     * @param string $projectId
     *
     * @return Database
     */
    protected function getInternalDB($projectId): Database
    {
        global $register;
        
        $cache = new Cache(new RedisCache($register->get('cache')));
        $dbForInternal = new Database(new MariaDB($register->get('db')), $cache);
        $dbForInternal->setNamespace('project_'.$projectId.'_internal'); // Main DB

        return $dbForInternal;
    }

    /**
     * @param string $projectId
     *
     * @return Database
     */
    protected function getExternalDB($projectId): Database
    {
        global $register;
        
        $cache = new Cache(new RedisCache($register->get('cache')));
        $dbForExternal = new Database(new MariaDB($register->get('db')), $cache);
        $dbForExternal->setNamespace('project_'.$projectId.'_external'); // Main DB

        return $dbForExternal;
    }
}
