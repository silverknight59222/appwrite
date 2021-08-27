<?php

/**
 * Init
 * 
 * Initializes both Appwrite API entry point, queue workers, and CLI tasks.
 * Set configuration, framework resources & app constants
 * 
 */
if (\file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('default_socket_timeout', -1);
error_reporting(E_ALL);

use Ahc\Jwt\JWT;
use Ahc\Jwt\JWTException;
use Appwrite\Auth\Auth;
use Appwrite\Database\Database as DatabaseOld;
use Appwrite\Database\Adapter\MySQL as MySQLAdapter;
use Appwrite\Database\Adapter\Redis as RedisAdapter;
use Appwrite\Event\Event;
use Appwrite\Network\Validator\Email;
use Appwrite\Network\Validator\IP;
use Appwrite\Network\Validator\URL;
use Appwrite\OpenSSL\OpenSSL;
use Appwrite\Stats\Stats;
use Utopia\App;
use Utopia\View;
use Utopia\Config\Config;
use Utopia\Locale\Locale;
use Utopia\Registry\Registry;
use MaxMind\Db\Reader;
use PHPMailer\PHPMailer\PHPMailer;
use Utopia\Cache\Adapter\Redis as RedisCache;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\MariaDB;
use Utopia\Database\Document;
use Utopia\Database\Database;
use Utopia\Database\Validator\Structure;
use Utopia\Database\Validator\Authorization;
use Utopia\Validator\Range;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;
use Utopia\Database\Query;

const APP_NAME = 'Appwrite';
const APP_DOMAIN = 'appwrite.io';
const APP_EMAIL_TEAM = 'team@localhost.test'; // Default email address
const APP_EMAIL_SECURITY = ''; // Default security email address
const APP_USERAGENT = APP_NAME.'-Server v%s. Please report abuse at %s';
const APP_MODE_DEFAULT = 'default';
const APP_MODE_ADMIN = 'admin';
const APP_PAGING_LIMIT = 12;
const APP_LIMIT_COUNT = 5000;
const APP_LIMIT_USERS = 10000;
const APP_CACHE_BUSTER = 151;
const APP_VERSION_STABLE = '0.9.4';
const APP_DATABASE_ATTRIBUTE_EMAIL = 'email';
const APP_DATABASE_ATTRIBUTE_IP = 'ip';
const APP_DATABASE_ATTRIBUTE_URL = 'url';
const APP_DATABASE_ATTRIBUTE_INT_RANGE = 'intRange';
const APP_DATABASE_ATTRIBUTE_FLOAT_RANGE = 'floatRange';
const APP_STORAGE_UPLOADS = '/storage/uploads';
const APP_STORAGE_FUNCTIONS = '/storage/functions';
const APP_STORAGE_CACHE = '/storage/cache';
const APP_STORAGE_CERTIFICATES = '/storage/certificates';
const APP_STORAGE_CONFIG = '/storage/config';
const APP_SOCIAL_TWITTER = 'https://twitter.com/appwrite_io';
const APP_SOCIAL_TWITTER_HANDLE = 'appwrite_io';
const APP_SOCIAL_FACEBOOK = 'https://www.facebook.com/appwrite.io';
const APP_SOCIAL_LINKEDIN = 'https://www.linkedin.com/company/appwrite';
const APP_SOCIAL_INSTAGRAM = 'https://www.instagram.com/appwrite.io';
const APP_SOCIAL_GITHUB = 'https://github.com/appwrite';
const APP_SOCIAL_DISCORD = 'https://appwrite.io/discord';
const APP_SOCIAL_DISCORD_CHANNEL = '564160730845151244';
const APP_SOCIAL_DEV = 'https://dev.to/appwrite';
const APP_SOCIAL_STACKSHARE = 'https://stackshare.io/appwrite'; 
// Database Worker Types
const DATABASE_TYPE_CREATE_ATTRIBUTE = 'createAttribute';
const DATABASE_TYPE_CREATE_INDEX = 'createIndex';
const DATABASE_TYPE_DELETE_ATTRIBUTE = 'deleteAttribute';
const DATABASE_TYPE_DELETE_INDEX = 'deleteIndex';
// Deletes Worker Types
const DELETE_TYPE_DOCUMENT = 'document';
const DELETE_TYPE_EXECUTIONS = 'executions';
const DELETE_TYPE_AUDIT = 'audit';
const DELETE_TYPE_ABUSE = 'abuse';
const DELETE_TYPE_CERTIFICATES = 'certificates';
// Mail Worker Types
const MAIL_TYPE_VERIFICATION = 'verification';
const MAIL_TYPE_RECOVERY = 'recovery';
const MAIL_TYPE_INVITATION = 'invitation';
// Auth Types
const APP_AUTH_TYPE_SESSION = 'Session';
const APP_AUTH_TYPE_JWT = 'JWT';
const APP_AUTH_TYPE_KEY = 'Key';
const APP_AUTH_TYPE_ADMIN = 'Admin';

$register = new Registry();

App::setMode(App::getEnv('_APP_ENV', App::MODE_TYPE_PRODUCTION));

/*
 * ENV vars
 */
Config::load('events', __DIR__.'/config/events.php');
Config::load('auth', __DIR__.'/config/auth.php');
Config::load('providers', __DIR__.'/config/providers.php');
Config::load('platforms', __DIR__.'/config/platforms.php');
Config::load('collections', __DIR__.'/config/collections.php');
Config::load('collections2', __DIR__.'/config/collections2.php');
Config::load('runtimes', __DIR__.'/config/runtimes.php');
Config::load('roles', __DIR__.'/config/roles.php');  // User roles and scopes
Config::load('scopes', __DIR__.'/config/scopes.php');  // User roles and scopes
Config::load('services', __DIR__.'/config/services.php');  // List of services
Config::load('variables', __DIR__.'/config/variables.php');  // List of env variables
Config::load('avatar-browsers', __DIR__.'/config/avatars/browsers.php'); 
Config::load('avatar-credit-cards', __DIR__.'/config/avatars/credit-cards.php'); 
Config::load('avatar-flags', __DIR__.'/config/avatars/flags.php'); 
Config::load('locale-codes', __DIR__.'/config/locale/codes.php'); 
Config::load('locale-currencies', __DIR__.'/config/locale/currencies.php'); 
Config::load('locale-eu', __DIR__.'/config/locale/eu.php'); 
Config::load('locale-languages', __DIR__.'/config/locale/languages.php'); 
Config::load('locale-phones', __DIR__.'/config/locale/phones.php'); 
Config::load('locale-countries', __DIR__.'/config/locale/countries.php');
Config::load('locale-continents', __DIR__.'/config/locale/continents.php');
Config::load('storage-logos', __DIR__.'/config/storage/logos.php'); 
Config::load('storage-mimes', __DIR__.'/config/storage/mimes.php'); 
Config::load('storage-inputs', __DIR__.'/config/storage/inputs.php'); 
Config::load('storage-outputs', __DIR__.'/config/storage/outputs.php'); 

$user = App::getEnv('_APP_REDIS_USER','');
$pass = App::getEnv('_APP_REDIS_PASS','');
if(!empty($user) || !empty($pass)) {
    Resque::setBackend('redis://'.$user.':'.$pass.'@'.App::getEnv('_APP_REDIS_HOST', '').':'.App::getEnv('_APP_REDIS_PORT', ''));
} else {
    Resque::setBackend(App::getEnv('_APP_REDIS_HOST', '').':'.App::getEnv('_APP_REDIS_PORT', ''));
}

/**
 * Old DB Filters
 */
DatabaseOld::addFilter('json',
    function($value) {
        if(!is_array($value)) {
            return $value;
        }
        return json_encode($value);
    },
    function($value) {
        return json_decode($value, true);
    }
);

DatabaseOld::addFilter('encrypt',
    function($value) {
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        
        return json_encode([
            'data' => OpenSSL::encrypt($value, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => bin2hex($iv),
            'tag' => bin2hex($tag),
            'version' => '1',
        ]);
    },
    function($value) {
        $value = json_decode($value, true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V'.$value['version']);

        return OpenSSL::decrypt($value['data'], $value['method'], $key, 0, hex2bin($value['iv']), hex2bin($value['tag']));
    }
);

/**
 * New DB Filters
 */
Database::addFilter('defaultValue',
    function($value) {
        return \json_encode(['value' => $value]);
    },
    function($value) {
        return \json_decode($value, true)['value'];
    }
);

Database::addFilter('subQueryAttributes',
    function($value) {
        return null;
    },
    function($value, Document $document, Database $database) {
        return $database
            ->find('attributes', [
                new Query('collectionId', Query::TYPE_EQUAL, [$document->getId()])
            ], 100, 0, []);
    }
);

Database::addFilter('subQueryIndexes',
    function($value) {
        return null;
    },
    function($value, Document $document, Database $database) {
        return $database
            ->find('indexes', [
                new Query('collectionId', Query::TYPE_EQUAL, [$document->getId()])
            ], 100, 0, []);
    }
);

Database::addFilter('encrypt',
    function($value) {
        $key = App::getEnv('_APP_OPENSSL_KEY_V1');
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength(OpenSSL::CIPHER_AES_128_GCM));
        $tag = null;
        return json_encode([
            'data' => OpenSSL::encrypt($value, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag),
            'method' => OpenSSL::CIPHER_AES_128_GCM,
            'iv' => bin2hex($iv),
            'tag' => bin2hex($tag),
            'version' => '1',
        ]);
    },
    function($value) {
        $value = json_decode($value, true);
        $key = App::getEnv('_APP_OPENSSL_KEY_V'.$value['version']);

        return OpenSSL::decrypt($value['data'], $value['method'], $key, 0, hex2bin($value['iv']), hex2bin($value['tag']));
    }
);

/**
 * DB Formats
 */
Structure::addFormat(APP_DATABASE_ATTRIBUTE_EMAIL, function() {
    return new Email();
}, Database::VAR_STRING);

Structure::addFormat(APP_DATABASE_ATTRIBUTE_IP, function() {
    return new IP();
}, Database::VAR_STRING);

Structure::addFormat(APP_DATABASE_ATTRIBUTE_URL, function() {
    return new URL();
}, Database::VAR_STRING);

Structure::addFormat(APP_DATABASE_ATTRIBUTE_INT_RANGE, function($attribute) {
    $min = $attribute['formatOptions']['min'] ?? -INF;
    $max = $attribute['formatOptions']['max'] ?? INF;
    return new Range($min, $max, Range::TYPE_INTEGER);
}, Database::VAR_INTEGER);

Structure::addFormat(APP_DATABASE_ATTRIBUTE_FLOAT_RANGE, function($attribute) {
    $min = $attribute['formatOptions']['min'] ?? -INF;
    $max = $attribute['formatOptions']['max'] ?? INF;
    return new Range($min, $max, Range::TYPE_FLOAT);
}, Database::VAR_FLOAT);

/*
 * Registry
 */
$register->set('dbPool', function () { // Register DB connection
    $dbHost = App::getEnv('_APP_DB_HOST', '');
    $dbPort = App::getEnv('_APP_DB_PORT', '');
    $dbUser = App::getEnv('_APP_DB_USER', '');
    $dbPass = App::getEnv('_APP_DB_PASS', '');
    $dbScheme = App::getEnv('_APP_DB_SCHEMA', '');

    $pool = new PDOPool((new PDOConfig())
        ->withHost($dbHost)
        ->withPort($dbPort)
        ->withDbName($dbScheme)
        ->withCharset('utf8mb4')
        ->withUsername($dbUser)
        ->withPassword($dbPass)
        ->withOptions([
            PDO::ATTR_ERRMODE => App::isDevelopment() ? PDO::ERRMODE_WARNING : PDO::ERRMODE_SILENT, // If in production mode, warnings are not displayed
        ])
    , 16);

    return $pool;
});
$register->set('redisPool', function () {
    $redisHost = App::getEnv('_APP_REDIS_HOST', '');
    $redisPort = App::getEnv('_APP_REDIS_PORT', '');
    $redisUser = App::getEnv('_APP_REDIS_USER', '');
    $redisPass = App::getEnv('_APP_REDIS_PASS', '');
    $redisAuth = '';

    if ($redisUser && $redisPass) {
        $redisAuth = $redisUser.':'.$redisPass;
    }

    $pool = new RedisPool((new RedisConfig)
        ->withHost($redisHost)
        ->withPort($redisPort)
        ->withAuth($redisAuth)
        ->withDbIndex(0)
    , 16);

    return $pool;
});
$register->set('influxdb', function () { // Register DB connection
    $host = App::getEnv('_APP_INFLUXDB_HOST', '');
    $port = App::getEnv('_APP_INFLUXDB_PORT', '');

    if (empty($host) || empty($port)) {
        return;
    }
    $driver = new InfluxDB\Driver\Curl("http://{$host}:{$port}");
    $client = new InfluxDB\Client($host, $port, '', '', false, false, 5);
    $client->setDriver($driver);

    return $client;
});
$register->set('statsd', function () { // Register DB connection
    $host = App::getEnv('_APP_STATSD_HOST', 'telegraf');
    $port = App::getEnv('_APP_STATSD_PORT', 8125);

    $connection = new \Domnikl\Statsd\Connection\UdpSocket($host, $port);
    $statsd = new \Domnikl\Statsd\Client($connection);

    return $statsd;
});
$register->set('smtp', function () {
    $mail = new PHPMailer(true);

    $mail->isSMTP();

    $username = App::getEnv('_APP_SMTP_USERNAME', null);
    $password = App::getEnv('_APP_SMTP_PASSWORD', null);

    $mail->XMailer = 'Appwrite Mailer';
    $mail->Host = App::getEnv('_APP_SMTP_HOST', 'smtp');
    $mail->Port = App::getEnv('_APP_SMTP_PORT', 25);
    $mail->SMTPAuth = (!empty($username) && !empty($password));
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = App::getEnv('_APP_SMTP_SECURE', false);
    $mail->SMTPAutoTLS = false;
    $mail->CharSet = 'UTF-8';

    $from = \urldecode(App::getEnv('_APP_SYSTEM_EMAIL_NAME', APP_NAME.' Server'));
    $email = App::getEnv('_APP_SYSTEM_EMAIL_ADDRESS', APP_EMAIL_TEAM);

    $mail->setFrom($email, $from);
    $mail->addReplyTo($email, $from);

    $mail->isHTML(true);

    return $mail;
});
$register->set('geodb', function () {
    return new Reader(__DIR__.'/db/DBIP/dbip-country-lite-2021-06.mmdb');
});

/*
 * Localization
 */
Locale::$exceptions = false;
Locale::setLanguageFromJSON('af', __DIR__.'/config/locale/translations/af.json');
Locale::setLanguageFromJSON('ar', __DIR__.'/config/locale/translations/ar.json');
Locale::setLanguageFromJSON('be', __DIR__.'/config/locale/translations/be.json');
Locale::setLanguageFromJSON('bg', __DIR__.'/config/locale/translations/bg.json');
Locale::setLanguageFromJSON('bn', __DIR__.'/config/locale/translations/bn.json');
Locale::setLanguageFromJSON('bs', __DIR__.'/config/locale/translations/bs.json');
Locale::setLanguageFromJSON('ca', __DIR__.'/config/locale/translations/ca.json');
Locale::setLanguageFromJSON('cs', __DIR__.'/config/locale/translations/cs.json');
Locale::setLanguageFromJSON('de', __DIR__.'/config/locale/translations/de.json');
Locale::setLanguageFromJSON('el', __DIR__.'/config/locale/translations/el.json');
Locale::setLanguageFromJSON('en', __DIR__.'/config/locale/translations/en.json');
Locale::setLanguageFromJSON('es', __DIR__.'/config/locale/translations/es.json');
Locale::setLanguageFromJSON('fa', __DIR__.'/config/locale/translations/fa.json');
Locale::setLanguageFromJSON('fi', __DIR__.'/config/locale/translations/fi.json');
Locale::setLanguageFromJSON('fo', __DIR__.'/config/locale/translations/fo.json');
Locale::setLanguageFromJSON('fr', __DIR__.'/config/locale/translations/fr.json');
Locale::setLanguageFromJSON('gu', __DIR__.'/config/locale/translations/gu.json');
Locale::setLanguageFromJSON('he', __DIR__.'/config/locale/translations/he.json');
Locale::setLanguageFromJSON('hi', __DIR__.'/config/locale/translations/hi.json');
Locale::setLanguageFromJSON('hu', __DIR__.'/config/locale/translations/hu.json');
Locale::setLanguageFromJSON('hy', __DIR__.'/config/locale/translations/hy.json');
Locale::setLanguageFromJSON('id', __DIR__.'/config/locale/translations/id.json');
Locale::setLanguageFromJSON('is', __DIR__.'/config/locale/translations/is.json');
Locale::setLanguageFromJSON('it', __DIR__.'/config/locale/translations/it.json');
Locale::setLanguageFromJSON('ja', __DIR__.'/config/locale/translations/ja.json');
Locale::setLanguageFromJSON('jv', __DIR__.'/config/locale/translations/jv.json');
Locale::setLanguageFromJSON('kn', __DIR__.'/config/locale/translations/kn.json');
Locale::setLanguageFromJSON('km', __DIR__.'/config/locale/translations/km.json');
Locale::setLanguageFromJSON('ko', __DIR__.'/config/locale/translations/ko.json');
Locale::setLanguageFromJSON('lt', __DIR__.'/config/locale/translations/lt.json');
Locale::setLanguageFromJSON('ml', __DIR__.'/config/locale/translations/ml.json');
Locale::setLanguageFromJSON('mr', __DIR__.'/config/locale/translations/mr.json');
Locale::setLanguageFromJSON('ms', __DIR__.'/config/locale/translations/ms.json');
Locale::setLanguageFromJSON('ne', __DIR__.'/config/locale/translations/ne.json');
Locale::setLanguageFromJSON('nl', __DIR__.'/config/locale/translations/nl.json');
Locale::setLanguageFromJSON('no', __DIR__.'/config/locale/translations/no.json');
Locale::setLanguageFromJSON('or', __DIR__.'/config/locale/translations/or.json');
Locale::setLanguageFromJSON('pa', __DIR__.'/config/locale/translations/pa.json');
Locale::setLanguageFromJSON('pl', __DIR__.'/config/locale/translations/pl.json');
Locale::setLanguageFromJSON('pt-br', __DIR__.'/config/locale/translations/pt-br.json');
Locale::setLanguageFromJSON('pt-pt', __DIR__.'/config/locale/translations/pt-pt.json');
Locale::setLanguageFromJSON('ro', __DIR__.'/config/locale/translations/ro.json');
Locale::setLanguageFromJSON('ru', __DIR__ . '/config/locale/translations/ru.json');
Locale::setLanguageFromJSON('si', __DIR__ . '/config/locale/translations/si.json');
Locale::setLanguageFromJSON('sl', __DIR__ . '/config/locale/translations/sl.json');
Locale::setLanguageFromJSON('sq', __DIR__ . '/config/locale/translations/sq.json');
Locale::setLanguageFromJSON('sv', __DIR__ . '/config/locale/translations/sv.json');
Locale::setLanguageFromJSON('ta', __DIR__ . '/config/locale/translations/ta.json');
Locale::setLanguageFromJSON('th', __DIR__.'/config/locale/translations/th.json');
Locale::setLanguageFromJSON('tl', __DIR__.'/config/locale/translations/tl.json');
Locale::setLanguageFromJSON('tr', __DIR__.'/config/locale/translations/tr.json');
Locale::setLanguageFromJSON('uk', __DIR__.'/config/locale/translations/uk.json');
Locale::setLanguageFromJSON('ur', __DIR__.'/config/locale/translations/ur.json');
Locale::setLanguageFromJSON('vi', __DIR__.'/config/locale/translations/vi.json');
Locale::setLanguageFromJSON('zh-cn', __DIR__.'/config/locale/translations/zh-cn.json');
Locale::setLanguageFromJSON('zh-tw', __DIR__.'/config/locale/translations/zh-tw.json');

\stream_context_set_default([ // Set global user agent and http settings
    'http' => [
        'method' => 'GET',
        'user_agent' => \sprintf(APP_USERAGENT,
            App::getEnv('_APP_VERSION', 'UNKNOWN'),
            App::getEnv('_APP_SYSTEM_SECURITY_EMAIL_ADDRESS', APP_EMAIL_SECURITY)),
        'timeout' => 2,
    ],
]);

// Runtime Execution

App::setResource('register', function() use ($register) {
    return $register;
});

App::setResource('layout', function($locale) {
    $layout = new View(__DIR__.'/views/layouts/default.phtml');
    $layout->setParam('locale', $locale);

    return $layout;
}, ['locale']);

App::setResource('locale', function() {
    return new Locale(App::getEnv('_APP_LOCALE', 'en'));
});

// Queues
App::setResource('events', function($register) {
    return new Event('', '');
}, ['register']);

App::setResource('audits', function($register) {
    return new Event(Event::AUDITS_QUEUE_NAME, Event::AUDITS_CLASS_NAME);
}, ['register']);

App::setResource('usage', function($register) {
    return new Stats($register->get('statsd'));
}, ['register']);

App::setResource('mails', function($register) {
    return new Event(Event::MAILS_QUEUE_NAME, Event::MAILS_CLASS_NAME);
}, ['register']);

App::setResource('deletes', function($register) {
    return new Event(Event::DELETE_QUEUE_NAME, Event::DELETE_CLASS_NAME);
}, ['register']);

App::setResource('database', function($register) {
    return new Event(Event::DATABASE_QUEUE_NAME, Event::DATABASE_CLASS_NAME);
}, ['register']);

// Test Mock
App::setResource('clients', function($request, $console, $project) {
    $console->setAttribute('platforms', [ // Always allow current host
        '$collection' => 'platforms',
        'name' => 'Current Host',
        'type' => 'web',
        'hostname' => $request->getHostname(),
    ], Document::SET_TYPE_APPEND);
    
    /**
     * Get All verified client URLs for both console and current projects
     * + Filter for duplicated entries
     */
    $clientsConsole = \array_map(function ($node) {
        return $node['hostname'];
    }, \array_filter($console->getAttribute('platforms', []), function ($node) {
        if (isset($node['type']) && $node['type'] === 'web' && isset($node['hostname']) && !empty($node['hostname'])) {
            return true;
        }

        return false;
    }));

    $clients = \array_unique(\array_merge($clientsConsole, \array_map(function ($node) {
        return $node['hostname'];
    }, \array_filter($project->getAttribute('platforms', []), function ($node) {
        if (isset($node['type']) && $node['type'] === 'web' && isset($node['hostname']) && !empty($node['hostname'])) {
            return true;
        }

        return false;
    }))));

    return $clients;
}, ['request', 'console', 'project']);

App::setResource('user', function($mode, $project, $console, $request, $response, $dbForInternal, $dbForConsole) {
    /** @var Utopia\Swoole\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Utopia\Database\Document $project */
    /** @var Utopia\Database\Database $dbForInternal */
    /** @var Utopia\Database\Database $dbForConsole */
    /** @var string $mode */

    Authorization::setDefaultStatus(true);

    Auth::setCookieName('a_session_'.$project->getId());

    if (APP_MODE_ADMIN === $mode) {
        Auth::setCookieName('a_session_'.$console->getId());
    }

    $session = Auth::decodeSession(
        $request->getCookie(Auth::$cookieName, // Get sessions
            $request->getCookie(Auth::$cookieName.'_legacy', '')));// Get fallback session from old clients (no SameSite support)

    // Get fallback session from clients who block 3rd-party cookies
    $response->addHeader('X-Debug-Fallback', 'false');

    if(empty($session['id']) && empty($session['secret'])) {
        $response->addHeader('X-Debug-Fallback', 'true');
        $fallback = $request->getHeader('x-fallback-cookies', '');
        $fallback = \json_decode($fallback, true);
        $session = Auth::decodeSession(((isset($fallback[Auth::$cookieName])) ? $fallback[Auth::$cookieName] : ''));
    }

    Auth::$unique = $session['id'] ?? '';
    Auth::$secret = $session['secret'] ?? '';

    if (APP_MODE_ADMIN !== $mode) {
        if ($project->isEmpty()) {
            $user = new Document(['$id' => '', '$collection' => 'users']);
        }
        else {
            $user = $dbForInternal->getDocument('users', Auth::$unique);
        }
    }
    else {
        $user = $dbForConsole->getDocument('users', Auth::$unique);
    }

    if ($user->isEmpty() // Check a document has been found in the DB
        || !Auth::sessionVerify($user->getAttribute('sessions', []), Auth::$secret)) { // Validate user has valid login token
        $user = new Document(['$id' => '', '$collection' => 'users']);
    }

    if (APP_MODE_ADMIN === $mode) {
        if ($user->find('teamId', $project->getAttribute('teamId'), 'memberships')) {
            Authorization::setDefaultStatus(false);  // Cancel security segmentation for admin users.
        } else {
            $user = new Document(['$id' => '', '$collection' => 'users']);
        }
    }

    $authJWT = $request->getHeader('x-appwrite-jwt', '');

    if (!empty($authJWT) && !$project->isEmpty()) { // JWT authentication
        $jwt = new JWT(App::getEnv('_APP_OPENSSL_KEY_V1'), 'HS256', 900, 10); // Instantiate with key, algo, maxAge and leeway.

        try {
            $payload = $jwt->decode($authJWT);
        } catch (JWTException $error) {
            throw new Exception('Failed to verify JWT. '.$error->getMessage(), 401);
        }
        
        $jwtUserId = $payload['userId'] ?? '';
        $jwtSessionId = $payload['sessionId'] ?? '';

        if($jwtUserId && $jwtSessionId) {
            $user = $dbForInternal->getDocument('users', $jwtUserId);
        }

        if (empty($user->find('$id', $jwtSessionId, 'sessions'))) { // Match JWT to active token
            $user = new Document(['$id' => '', '$collection' => 'users']);
        }
    }

    return $user;
}, ['mode', 'project', 'console', 'request', 'response', 'dbForInternal', 'dbForConsole']);

App::setResource('project', function($dbForConsole, $request, $console) {
    /** @var Utopia\Swoole\Request $request */
    /** @var Utopia\Database\Database $dbForConsole */
    /** @var Utopia\Database\Document $console */

    $projectId = $request->getParam('project',
        $request->getHeader('x-appwrite-project', 'console'));
    
    if($projectId === 'console') {
        return $console;
    }

    Authorization::disable();

    $project = $dbForConsole->getDocument('projects', $projectId);

    Authorization::reset();

    return $project;
}, ['dbForConsole', 'request', 'console']);

App::setResource('console', function() {
    return new Document([
        '$id' => 'console',
        'name' => 'Appwrite',
        '$collection' => 'projects',
        'description' => 'Appwrite core engine',
        'logo' => '',
        'teamId' => -1,
        'webhooks' => [],
        'keys' => [],
        'platforms' => [
            [
                '$collection' => 'platforms',
                'name' => 'Production',
                'type' => 'web',
                'hostname' => 'appwrite.io',
            ],
            [
                '$collection' => 'platforms',
                'name' => 'Development',
                'type' => 'web',
                'hostname' => 'appwrite.test',
            ],
            [
                '$collection' => 'platforms',
                'name' => 'Localhost',
                'type' => 'web',
                'hostname' => 'localhost',
            ], // Current host is added on app init
        ],
        'legalName' => '',
        'legalCountry' => '',
        'legalState' => '',
        'legalCity' => '',
        'legalAddress' => '',
        'legalTaxId' => '',
        'auths' => [
            'limit' => (App::getEnv('_APP_CONSOLE_WHITELIST_ROOT', 'enabled') === 'enabled') ? 1 : 0, // limit signup to 1 user
        ],
        'authWhitelistEmails' => (!empty(App::getEnv('_APP_CONSOLE_WHITELIST_EMAILS', null))) ? \explode(',', App::getEnv('_APP_CONSOLE_WHITELIST_EMAILS', null)) : [],
        'authWhitelistIPs' => (!empty(App::getEnv('_APP_CONSOLE_WHITELIST_IPS', null))) ? \explode(',', App::getEnv('_APP_CONSOLE_WHITELIST_IPS', null)) : [],
    ]);
}, []);

App::setResource('consoleDB', function($db, $cache) {
    $consoleDB = new DatabaseOld();
    $consoleDB->setAdapter(new RedisAdapter(new MySQLAdapter($db, $cache), $cache));
    $consoleDB->setNamespace('app_console'); // Should be replaced with param if we want to have parent projects
    $consoleDB->setMocks(Config::getParam('collections', []));

    return $consoleDB;
}, ['db', 'cache']);

App::setResource('projectDB', function($db, $cache, $project) {
    $projectDB = new DatabaseOld();
    $projectDB->setAdapter(new RedisAdapter(new MySQLAdapter($db, $cache), $cache));
    $projectDB->setNamespace('app_'.$project->getId());
    $projectDB->setMocks(Config::getParam('collections', []));

    return $projectDB;
}, ['db', 'cache', 'project']);

App::setResource('dbForInternal', function($db, $cache, $project) {
    $cache = new Cache(new RedisCache($cache));

    $database = new Database(new MariaDB($db), $cache);
    $database->setNamespace('project_'.$project->getId().'_internal');

    return $database;
}, ['db', 'cache', 'project']);

App::setResource('dbForExternal', function($db, $cache, $project) {
    $cache = new Cache(new RedisCache($cache));

    $database = new Database(new MariaDB($db), $cache);
    $database->setNamespace('project_'.$project->getId().'_external');

    return $database;
}, ['db', 'cache', 'project']);

App::setResource('dbForConsole', function($db, $cache) {
    $cache = new Cache(new RedisCache($cache));

    $database = new Database(new MariaDB($db), $cache);
    $database->setNamespace('project_console_internal');

    return $database;
}, ['db', 'cache']);

App::setResource('mode', function($request) {
    /** @var Utopia\Swoole\Request $request */
    return $request->getParam('mode', $request->getHeader('x-appwrite-mode', APP_MODE_DEFAULT));
}, ['request']);

App::setResource('geodb', function($register) {
    /** @var Utopia\Registry\Registry $register */
    return $register->get('geodb');
}, ['register']);
