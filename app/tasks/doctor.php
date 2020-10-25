<?php

global $cli;

use Appwrite\ClamAV\Network;
use Appwrite\Storage\Device\Local;
use Appwrite\Storage\Storage;
use Utopia\App;
use Utopia\CLI\Console;
use Utopia\Domains\Domain;

$cli
    ->task('doctor')
    ->desc('Validate server health')
    ->action(function () use ($register) {
        Console::log("  __   ____  ____  _  _  ____  __  ____  ____     __  __  
 / _\ (  _ \(  _ \/ )( \(  _ \(  )(_  _)(  __)   (  )/  \ 
/    \ ) __/ ) __/\ /\ / )   / )(   )(   ) _)  _  )((  O )
\_/\_/(__)  (__)  (_/\_)(__\_)(__) (__) (____)(_)(__)\__/ ");

        Console::log("\n".'👩‍⚕️ Running '.APP_NAME.' Doctor for version '.App::getEnv('_APP_VERSION', 'UNKNOWN').' ...'."\n");

        Console::log('Checking for production best practices...');
        
        $domain = new Domain(App::getEnv('_APP_DOMAIN'));

        if(!$domain->isKnown() || $domain->isTest()) {
            Console::log('🔴 Hostname has a public suffix');
        }
        else {
            Console::log('🟢 Hostname has a public suffix');
        }
        
        $domain = new Domain(App::getEnv('_APP_DOMAIN_TARGET'));

        if(!$domain->isKnown() || $domain->isTest()) {
            Console::log('🔴 CNAME target has a public suffix');
        }
        else {
            Console::log('🟢 CNAME target has a public suffix');
        }
        
        if(App::getEnv('_APP_OPENSSL_KEY_V1', 'your-secret-key') === 'your-secret-key') {
            Console::log('🔴 Using a unique secret key for encryption');
        }
        else {
            Console::log('🟢 Using a unique secret key for encryption');
        }

        if(App::getEnv('_APP_ENV', 'development') === 'development') {
            Console::log('🔴 App enviornment is set for production');
        }
        else {
            Console::log('🟢 App enviornment is set for production');
        }

        if(App::getEnv('_APP_OPTIONS_ABUSE', 'disabled') === 'disabled') {
            Console::log('🔴 Abuse protection is enabled');
        }
        else {
            Console::log('🟢 Abuse protection is enabled');
        }

        $authWhitelistEmails = App::getEnv('_APP_CONSOLE_WHITELIST_EMAILS', null);
        $authWhitelistIPs = App::getEnv('_APP_CONSOLE_WHITELIST_IPS', null);
        $authWhitelistDomains = App::getEnv('_APP_CONSOLE_WHITELIST_DOMAINS', null);

        if(empty($authWhitelistEmails)
            && empty($authWhitelistDomains)
            && empty($authWhitelistIPs)
        ) {
            Console::log('🔴 Console access limits are disabled');
        }
        else {
            Console::log('🟢 Console access limits are enabled');
        }
        
        if(empty(App::getEnv('_APP_OPTIONS_FORCE_HTTPS', null))) {
            Console::log('🔴 HTTP force option is disabled');
        }
        else {
            Console::log('🟢 HTTP force option is enabled');
        }

        \sleep(0.2);

        try {
            Console::log("\n".'Checking connectivity...');
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $register->get('db'); /* @var $db PDO */
            Console::success('Database............connected 👍');
        } catch (\Throwable $th) {
            Console::error('Database.........disconnected 👎');
        }

        try {
            $register->get('cache');
            Console::success('Queue...............connected 👍');
        } catch (\Throwable $th) {
            Console::error('Queue............disconnected 👎');
        }

        try {
            $register->get('cache');
            Console::success('Cache...............connected 👍');
        } catch (\Throwable $th) {
            Console::error('Cache............disconnected 👎');
        }

        if(App::getEnv('_APP_STORAGE_ANTIVIRUS') === 'enabled') { // Check if scans are enabled
            try {
                $antiVirus = new Network('clamav', 3310);

                if((@$antiVirus->ping())) {
                    Console::success('AntiVirus...........connected 👍');
                }
                else {
                    Console::error('AntiVirus........disconnected 👎');
                }
            } catch (\Throwable $th) {
                Console::error('AntiVirus........disconnected 👎');
            }
        }

        try {
            $mail = $register->get('smtp'); /* @var $mail \PHPMailer\PHPMailer\PHPMailer */

            $mail->addAddress('demo@example.com', 'Example.com');
            $mail->Subject = 'Test SMTP Connection';
            $mail->Body = 'Hello World';
            $mail->AltBody = 'Hello World';
    
            $mail->send();
            Console::success('SMTP................connected 👍');
        } catch (\Throwable $th) {
            Console::error('SMTP.............disconnected 👎');
        }

        $host = App::getEnv('_APP_STATSD_HOST', 'telegraf');
        $port = App::getEnv('_APP_STATSD_PORT', 8125);

        if($fp = @\fsockopen('udp://'.$host, $port, $errCode, $errStr, 2)){   
            Console::success('StatsD..............connected 👍');
            \fclose($fp);
        } else {
            Console::error('StatsD...........disconnected 👎');
        }

        $host = App::getEnv('_APP_INFLUXDB_HOST', '');
        $port = App::getEnv('_APP_INFLUXDB_PORT', '');

        if($fp = @\fsockopen($host, $port, $errCode, $errStr, 2)){   
            Console::success('InfluxDB............connected 👍');
            \fclose($fp);
        } else {
            Console::error('InfluxDB.........disconnected 👎');
        }

        \sleep(0.2);

        Console::log('');
        Console::log('Checking volumes...');

        foreach ([
            'Uploads' => APP_STORAGE_UPLOADS,
            'Cache' => APP_STORAGE_CACHE,
            'Config' => APP_STORAGE_CONFIG,
            'Certs' => APP_STORAGE_CERTIFICATES
        ] as $key => $volume) {
            $device = new Local($volume);

            if (\is_readable($device->getRoot())) {
                Console::success('🟢 '.$key.' Volume is readable');
            }
            else {
                Console::error('🔴 '.$key.' Volume is unreadable');
            }
            
            if (\is_writable($device->getRoot())) {
                Console::success('🟢 '.$key.' Volume is writeable');
            }
            else {
                Console::error('🔴 '.$key.' Volume is unwriteable');
            }
        }

        \sleep(0.2);

        Console::log('');
        Console::log('Checking disk space usage...');

        foreach ([
            'Uploads' => APP_STORAGE_UPLOADS,
            'Cache' => APP_STORAGE_CACHE,
            'Config' => APP_STORAGE_CONFIG,
            'Certs' => APP_STORAGE_CERTIFICATES
        ] as $key => $volume) {
            $device = new Local($volume);

            $percentage = (($device->getPartitionTotalSpace() - $device->getPartitionFreeSpace())
            / $device->getPartitionTotalSpace()) * 100;
    
            $message = $key.' Volume has '.Storage::human($device->getPartitionFreeSpace()) . ' free space ('.\round($percentage, 2).'% used)';
    
            if ($percentage < 80) {
                Console::success('🟢 ' . $message);
            }
            else {
                Console::error('🔴 ' . $message);
            }
        }

        
        try {
            Console::log('');
            $version = \json_decode(@\file_get_contents(App::getEnv('_APP_HOME', 'http://localhost').'/v1/health/version'), true);
            
            if($version && isset($version['version'])) {
                if(\version_compare($version['version'], App::getEnv('_APP_VERSION', 'UNKNOWN')) === 0) {
                    Console::info('You are running the latest version of '.APP_NAME.'! 🥳');
                }
                else {
                    Console::info('A new version ('.$version['version'].') is available! 🥳'."\n");
                }
            }
            else {
                Console::error('Failed to check for a newer version'."\n");
            }
        } catch (\Throwable $th) {
            Console::error('Failed to check for a newer version'."\n");
        }
    });