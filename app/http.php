<?php

require_once __DIR__.'/../vendor/autoload.php';

use Appwrite\Database\Validator\Authorization;
use Utopia\Swoole\Files;
use Utopia\Swoole\Request;
use Appwrite\Utopia\Response;
use Swoole\Process;
use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\App;
use Utopia\CLI\Console;
use Utopia\Config\Config;
use Utopia\Database\Validator\Authorization as Authorization2;

// xdebug_start_trace('/tmp/trace');

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('default_socket_timeout', -1);
error_reporting(E_ALL);

$http = new Server("0.0.0.0", App::getEnv('PORT', 80));

$payloadSize = max(4000000 /* 4mb */, App::getEnv('_APP_STORAGE_LIMIT', 10000000 /* 10mb */));

$http
    ->set([
        'open_http2_protocol' => true,
        // 'document_root' => __DIR__.'/../public',
        // 'enable_static_handler' => true,
        'http_compression' => true,
        'http_compression_level' => 6,
        'package_max_length' => $payloadSize,
        'buffer_output_size' => $payloadSize,
    ])
;

$http->on('WorkerStart', function($serv, $workerId) {
    Console::success('Worker '.++$workerId.' started succefully');
});

$http->on('BeforeReload', function($serv, $workerId) {
    Console::success('Starting reload...');
});

$http->on('AfterReload', function($serv, $workerId) {
    Console::success('Reload completed...');
});

Files::load(__DIR__ . '/../public');

include __DIR__ . '/controllers/general.php';

$http->on('start', function (Server $http) use ($payloadSize, $register) {
    $app = new App('UTC');
    $dbForConsole = $app->getResource('dbForConsole'); /** @var Utopia\Database\Database $dbForConsole */

    if(!$dbForConsole->exists()) {
        Console::success('[Setup] - Server database init started...');
        
        $collections = Config::getParam('collections2', []); /** @var array $collections */

        $register->get('cache')->flushAll();

        $dbForConsole->create();

        foreach ($collections as $key => $collection) {
            $dbForConsole->createCollection($key);

            foreach ($collection['attributes'] as $i => $attribute) {
                $dbForConsole->createAttribute(
                    $key,
                    $attribute['$id'],
                    $attribute['type'],
                    $attribute['size'],
                    $attribute['required'],
                    $attribute['signed'],
                    $attribute['array'],
                    $attribute['filters'],
                );
            }

            foreach ($collection['indexes'] as $i => $index) {
                $dbForConsole->createIndex(
                    $key,
                    $index['$id'],
                    $index['type'],
                    $index['attributes'],
                    $index['lengths'],
                    $index['orders'],
                );
            }
        }

        Console::success('[Setup] - Server database init completed...');
    }

    Console::success('Server started succefully (max payload is '.number_format($payloadSize).' bytes)');

    Console::info("Master pid {$http->master_pid}, manager pid {$http->manager_pid}");

    // listen ctrl + c
    Process::signal(2, function () use ($http) {
        Console::log('Stop by Ctrl+C');
        $http->shutdown();
    });
});

$http->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) {
    $request = new Request($swooleRequest);
    $response = new Response($swooleResponse);

    if(Files::isFileLoaded($request->getURI())) {
        $time = (60 * 60 * 24 * 365 * 2); // 45 days cache

        $response
            ->setContentType(Files::getFileMimeType($request->getURI()))
            ->addHeader('Cache-Control', 'public, max-age='.$time)
            ->addHeader('Expires', \date('D, d M Y H:i:s', \time() + $time).' GMT') // 45 days cache
            ->send(Files::getFileContents($request->getURI()))
        ;

        return;
    }

    $app = new App('UTC');
    
    try {
        Authorization::cleanRoles();
        Authorization::setRole('role:all');

        Authorization2::cleanRoles();
        Authorization2::setRole('role:all');

        $app->run($request, $response);
    } catch (\Throwable $th) {
        Console::error('[Error] Type: '.get_class($th));
        Console::error('[Error] Message: '.$th->getMessage());
        Console::error('[Error] File: '.$th->getFile());
        Console::error('[Error] Line: '.$th->getLine());

        if(App::isDevelopment()) {
            $swooleResponse->end('error: '.$th->getMessage());
        }
        else {
            $swooleResponse->end('500: Server Error');
        }
    }
});

$http->start();