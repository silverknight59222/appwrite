<?php

use Appwrite\Resque\Worker;
use Utopia\Audit\Audit;
use Utopia\Cache\Adapter\Redis;
use Utopia\Cache\Cache;
use Utopia\CLI\Console;
use Utopia\Database\Adapter\MariaDB;
use Utopia\Database\Database;

require_once __DIR__.'/../workers.php';

Console::title('Audits V1 Worker');
Console::success(APP_NAME.' audits worker v1 has started');

class AuditsV1 extends Worker
{
    public $args = [];

    public function init(): void
    {
    }

    public function run(): void
    {
        $projectId = $this->args['projectId'];
        $userId = $this->args['userId'];
        $event = $this->args['event'];
        $resource = $this->args['resource'];
        $userAgent = $this->args['userAgent'];
        $ip = $this->args['ip'];
        $data = $this->args['data'];
        
        $dbForInternal = getInternalDB($projectId);
        $audit = new Audit($dbForInternal);

        $audit->log($userId, $event, $resource, $userAgent, $ip, '', $data);
    }

    public function shutdown(): void
    {
        // ... Remove environment for this job
    }
}