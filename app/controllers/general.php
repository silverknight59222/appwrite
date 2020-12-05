<?php

require_once __DIR__.'/../init.php';

use Utopia\App;
use Utopia\Swoole\Request;
use Appwrite\Utopia\Response;
use Utopia\View;
use Utopia\Exception;
use Utopia\Config\Config;
use Utopia\Domains\Domain;
use Appwrite\Auth\Auth;
use Appwrite\Database\Database;
use Appwrite\Database\Document;
use Appwrite\Database\Validator\Authorization;
use Appwrite\Network\Validator\Origin;
use Appwrite\Storage\Device\Local;
use Appwrite\Storage\Storage;
use Utopia\CLI\Console;

Config::setParam('domainVerification', false);
Config::setParam('cookieDomain', 'localhost');
Config::setParam('cookieSamesite', Response::COOKIE_SAMESITE_NONE);

App::init(function ($utopia, $request, $response, $console, $project, $user, $locale, $webhooks, $audits, $usage, $deletes, $functions, $clients) {
    /** @var Utopia\Swoole\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Appwrite\Database\Document $console */
    /** @var Appwrite\Database\Document $project */
    /** @var Appwrite\Database\Document $user */
    /** @var Utopia\Locale\Locale $locale */
    /** @var Appwrite\Event\Event $webhooks */
    /** @var Appwrite\Event\Event $audits */
    /** @var Appwrite\Event\Event $usage */
    /** @var Appwrite\Event\Event $deletes */
    /** @var Appwrite\Event\Event $functions */

    /** @var bool $mode */
    /** @var array $clients */

    $localeParam = (string)$request->getParam('locale', $request->getHeader('x-appwrite-locale', ''));

    if (\in_array($localeParam, Config::getParam('locale-codes'))) {
        $locale->setDefault($localeParam);
    };

    $route = $utopia->match($request);

    if (!empty($route->getLabel('sdk.platform', [])) && empty($project->getId()) && ($route->getLabel('scope', '') !== 'public')) {
        throw new Exception('Missing or unknown project ID', 400);
    }

    $console->setAttribute('platforms', [ // Allways allow current host
        '$collection' => Database::SYSTEM_COLLECTION_PLATFORMS,
        'name' => 'Current Host',
        'type' => 'web',
        'hostname' => $request->getHostname(),
    ], Document::SET_TYPE_APPEND);

    $referrer = $request->getReferer();
    $origin = \parse_url($request->getOrigin($referrer), PHP_URL_HOST);
    $protocol = \parse_url($request->getOrigin($referrer), PHP_URL_SCHEME);
    $port = \parse_url($request->getOrigin($referrer), PHP_URL_PORT);

    $refDomain = (!empty($protocol) ? $protocol : $request->getProtocol()).'://'.((\in_array($origin, $clients))
        ? $origin : 'localhost') . (!empty($port) ? ':'.$port : '');

    $selfDomain = new Domain($request->getHostname());
    $endDomain = new Domain((string)$origin);

    // var_dump('referer', $referrer);
    // var_dump('origin', $origin);
    // var_dump('port', $request->getPort());
    // var_dump('hostname', $request->getHostname());
    // var_dump('protocol', $request->getProtocol());
    // var_dump('method', $request->getMethod());
    // var_dump('ip', $request->getIP());
    // var_dump('-----------------');
    // var_dump($request->debug());

    Config::setParam('domainVerification',
        ($selfDomain->getRegisterable() === $endDomain->getRegisterable()) &&
            $endDomain->getRegisterable() !== '');
        
    Config::setParam('cookieDomain', (
        $request->getHostname() === 'localhost' ||
        $request->getHostname() === 'localhost:'.$request->getPort() ||
        (\filter_var($request->getHostname(), FILTER_VALIDATE_IP) !== false)
    )
        ? null
        : '.'.$request->getHostname()
    );

    Storage::setDevice('files', new Local(APP_STORAGE_UPLOADS.'/app-'.$project->getId()));
    Storage::setDevice('functions', new Local(APP_STORAGE_FUNCTIONS.'/app-'.$project->getId()));

    /*
     * Security Headers
     *
     * As recommended at:
     * @see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
     */
    if (App::getEnv('_APP_OPTIONS_FORCE_HTTPS', 'disabled') === 'enabled') { // Force HTTPS
        if ($request->getProtocol() !== 'https') {
            return $response->redirect('https://'.$request->getHostname().$request->getURI());
        }

        $response->addHeader('Strict-Transport-Security', 'max-age='.(60 * 60 * 24 * 126)); // 126 days
    }    

    $response
        ->addHeader('Server', 'Appwrite')
        ->addHeader('X-XSS-Protection', '1; mode=block; report=/v1/xss?url='.\urlencode($request->getURI()))
        //->addHeader('X-Frame-Options', ($refDomain == 'http://localhost') ? 'SAMEORIGIN' : 'ALLOW-FROM ' . $refDomain)
        ->addHeader('X-Content-Type-Options', 'nosniff')
        ->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE')
        ->addHeader('Access-Control-Allow-Headers', 'Origin, Cookie, Set-Cookie, X-Requested-With, Content-Type, Access-Control-Allow-Origin, Access-Control-Request-Headers, Accept, X-Appwrite-Project, X-Appwrite-Key, X-Appwrite-Locale, X-Appwrite-Mode, X-SDK-Version, Cache-Control, Expires, Pragma')
        ->addHeader('Access-Control-Expose-Headers', 'X-Fallback-Cookies')
        ->addHeader('Access-Control-Allow-Origin', $refDomain)
        ->addHeader('Access-Control-Allow-Credentials', 'true')
    ;

    /*
     * Validate Client Domain - Check to avoid CSRF attack
     *  Adding Appwrite API domains to allow XDOMAIN communication
     *  Skip this check for non-web platforms which are not requiredto send an origin header
     */
    $origin = $request->getOrigin($request->getReferer(''));
    $originValidator = new Origin(\array_merge($project->getAttribute('platforms', []), $console->getAttribute('platforms', [])));

    if (!$originValidator->isValid($origin)
        && \in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH, Request::METHOD_DELETE])
        && $route->getLabel('origin', false) !== '*'
        && empty($request->getHeader('x-appwrite-key', ''))) {
        throw new Exception($originValidator->getDescription(), 403);
    }
    
    /*
     * ACL Check
     */
    $role = ($user->isEmpty()) ? Auth::USER_ROLE_GUEST : Auth::USER_ROLE_MEMBER;

    // Add user roles
    $membership = $user->search('teamId', $project->getAttribute('teamId', null), $user->getAttribute('memberships', []));

    if ($membership) {
        foreach ($membership->getAttribute('roles', []) as $memberRole) {
            switch ($memberRole) {
                case 'owner':
                    $role = Auth::USER_ROLE_OWNER;
                    break;
                case 'admin':
                    $role = Auth::USER_ROLE_ADMIN;
                    break;
                case 'developer':
                    $role = Auth::USER_ROLE_DEVELOPER;
                    break;
            }
        }
    }

    $roles = Config::getParam('roles', []);
    $scope = $route->getLabel('scope', 'none'); // Allowed scope for chosen route
    $scopes = $roles[$role]['scopes']; // Allowed scopes for user role
    
    // Check if given key match project API keys
    $key = $project->search('secret', $request->getHeader('x-appwrite-key', ''), $project->getAttribute('keys', []));
    
    /*
     * Try app auth when we have project key and no user
     *  Mock user to app and grant API key scopes in addition to default app scopes
     */
    if (null !== $key && $user->isEmpty()) {
        $user = new Document([
            '$id' => '',
            'status' => Auth::USER_STATUS_ACTIVATED,
            'email' => 'app.'.$project->getId().'@service.'.$request->getHostname(),
            'password' => '',
            'name' => $project->getAttribute('name', 'Untitled'),
        ]);

        $role = Auth::USER_ROLE_APP;
        $scopes = \array_merge($roles[$role]['scopes'], $key->getAttribute('scopes', []));

        Authorization::setDefaultStatus(false);  // Cancel security segmentation for API keys.
    }

    if ($user->getId()) {
        Authorization::setRole('user:'.$user->getId());
    }

    Authorization::setRole('role:'.$role);

    \array_map(function ($node) {
        if (isset($node['teamId']) && isset($node['roles'])) {
            Authorization::setRole('team:'.$node['teamId']);

            foreach ($node['roles'] as $nodeRole) { // Set all team roles
                Authorization::setRole('team:'.$node['teamId'].'/'.$nodeRole);
            }
        }
    }, $user->getAttribute('memberships', []));

    // TDOO Check if user is god

    if (!\in_array($scope, $scopes)) {
        if (empty($project->getId()) || Database::SYSTEM_COLLECTION_PROJECTS !== $project->getCollection()) { // Check if permission is denied because project is missing
            throw new Exception('Project not found', 404);
        }
        
        throw new Exception($user->getAttribute('email', 'User').' (role: '.\strtolower($roles[$role]['label']).') missing scope ('.$scope.')', 401);
    }

    if (Auth::USER_STATUS_BLOCKED == $user->getAttribute('status')) { // Account has not been activated
        throw new Exception('Invalid credentials. User is blocked', 401); // User is in status blocked
    }

    if ($user->getAttribute('reset')) {
        throw new Exception('Password reset is required', 412);
    }

    /*
     * Background Jobs
     */
    $functions
        ->setParam('projectId', $project->getId())
        ->setParam('event', $route->getLabel('event', ''))
        ->setParam('payload', [])
        ->setParam('functionId', null)
        ->setParam('executionId', null)
        ->setParam('trigger', 'event')
    ;

    $webhooks
        ->setParam('projectId', $project->getId())
        ->setParam('userId', $user->getId())
        ->setParam('event', $route->getLabel('event', ''))
        ->setParam('payload', [])
    ;

    $audits
        ->setParam('projectId', $project->getId())
        ->setParam('userId', $user->getId())
        ->setParam('event', '')
        ->setParam('resource', '')
        ->setParam('userAgent', $request->getUserAgent(''))
        ->setParam('ip', $request->getIP())
        ->setParam('data', [])
    ;

    $usage
        ->setParam('projectId', $project->getId())
        ->setParam('httpRequest', 1)
        ->setParam('httpUrl', $request->getHostname().$request->getURI())
        ->setParam('httpMethod', $request->getMethod())
        ->setParam('networkRequestSize', 0)
        ->setParam('networkResponseSize', 0)
        ->setParam('storage', 0)
    ;
    
    $deletes
        ->setParam('projectId', $project->getId())
    ;
}, ['utopia', 'request', 'response', 'console', 'project', 'user', 'locale', 'webhooks', 'audits', 'usage', 'deletes', 'functions', 'clients']);

App::shutdown(function ($utopia, $request, $response, $project, $webhooks, $audits, $usage, $deletes, $functions, $mode) {
    /** @var Utopia\App $utopia */
    /** @var Utopia\Swoole\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Appwrite\Database\Document $project */
    /** @var Appwrite\Event\Event $webhooks */
    /** @var Appwrite\Event\Event $audits */
    /** @var Appwrite\Event\Event $usage */
    /** @var Appwrite\Event\Event $deletes */
    /** @var Appwrite\Event\Event $functions */
    /** @var bool $mode */

    if (!empty($functions->getParam('event'))) {
        if(empty($functions->getParam('payload'))) {
            $functions->setParam('payload', $response->getPayload());
        }

        $functions->trigger();
    }

    if (!empty($webhooks->getParam('event'))) {
        if(empty($webhooks->getParam('payload'))) {
            $webhooks->setParam('payload', $response->getPayload());
        }

        $webhooks->trigger();
    }
    
    if (!empty($audits->getParam('event'))) {
        $audits->trigger();
    }
    
    if (!empty($deletes->getParam('document'))) {
        $deletes->trigger();
    }
    
    $route = $utopia->match($request);
    
    if ($project->getId()
        && $mode !== APP_MODE_ADMIN //TODO: add check to make sure user is admin
        && !empty($route->getLabel('sdk.namespace', null))) { // Don't calculate console usage on admin mode
        
        $usage
            ->setParam('networkRequestSize', $request->getSize() + $usage->getParam('storage'))
            ->setParam('networkResponseSize', $response->getSize())
            ->trigger()
        ;
    }
}, ['utopia', 'request', 'response', 'project', 'webhooks', 'audits', 'usage', 'deletes', 'functions', 'mode']);

App::options(function ($request, $response) {
    /** @var Utopia\Swoole\Request $request */
    /** @var Appwrite\Utopia\Response $response */

    $origin = $request->getOrigin();

    $response
        ->addHeader('Server', 'Appwrite')
        ->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE')
        ->addHeader('Access-Control-Allow-Headers', 'Origin, Cookie, Set-Cookie, X-Requested-With, Content-Type, Access-Control-Allow-Origin, Access-Control-Request-Headers, Accept, X-Appwrite-Project, X-Appwrite-Key, X-Appwrite-Locale, X-Appwrite-Mode, X-SDK-Version, Cache-Control, Expires, Pragma, X-Fallback-Cookies')
        ->addHeader('Access-Control-Expose-Headers', 'X-Fallback-Cookies')
        ->addHeader('Access-Control-Allow-Origin', $origin)
        ->addHeader('Access-Control-Allow-Credentials', 'true')
        ->send();
}, ['request', 'response']);

App::error(function ($error, $utopia, $request, $response, $layout, $project) {
    /** @var Exception $error */
    /** @var Utopia\App $utopia */
    /** @var Utopia\Swoole\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Utopia\View $layout */
    /** @var Appwrite\Database\Document $project */

    $route = $utopia->match($request);
    $template = ($route) ? $route->getLabel('error', null) : null;

    if (php_sapi_name() === 'cli') {
        Console::error('[Error] Method: '.$route->getMethod());
        Console::error('[Error] URL: '.$route->getURL());
        Console::error('[Error] Type: '.get_class($error));
        Console::error('[Error] Message: '.$error->getMessage());
        Console::error('[Error] File: '.$error->getFile());
        Console::error('[Error] Line: '.$error->getLine());
    }

    $version = App::getEnv('_APP_VERSION', 'UNKNOWN');

    switch ($error->getCode()) {
        case 400: // Error allowed publicly
        case 401: // Error allowed publicly
        case 402: // Error allowed publicly
        case 403: // Error allowed publicly
        case 404: // Error allowed publicly
        case 409: // Error allowed publicly
        case 412: // Error allowed publicly
        case 429: // Error allowed publicly
            $code = $error->getCode();
            $message = $error->getMessage();
            break;
        default:
            $code = 500; // All other errors get the generic 500 server error status code
            $message = 'Server Error';
    }

    //$_SERVER = []; // Reset before reporting to error log to avoid keys being compromised

    $output = ((App::isDevelopment())) ? [
        'message' => $error->getMessage(),
        'code' => $error->getCode(),
        'file' => $error->getFile(),
        'line' => $error->getLine(),
        'trace' => $error->getTrace(),
        'version' => $version,
    ] : [
        'message' => $message,
        'code' => $code,
        'version' => $version,
    ];

    $response
        ->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->addHeader('Expires', '0')
        ->addHeader('Pragma', 'no-cache')
        ->setStatusCode($code)
    ;

    if ($template) {
        $comp = new View($template);

        $comp
            ->setParam('projectName', $project->getAttribute('name'))
            ->setParam('projectURL', $project->getAttribute('url'))
            ->setParam('message', $error->getMessage())
            ->setParam('code', $code)
        ;

        $layout
            ->setParam('title', $project->getAttribute('name').' - Error')
            ->setParam('description', 'No Description')
            ->setParam('body', $comp)
            ->setParam('version', $version)
            ->setParam('litespeed', false)
        ;

        $response->html($layout->render());
    }

    $response->dynamic(new Document($output),
        $utopia->isDevelopment() ? Response::MODEL_ERROR_DEV : Response::MODEL_LOCALE);
}, ['error', 'utopia', 'request', 'response', 'layout', 'project']);

App::get('/manifest.json')
    ->desc('Progressive app manifest file')
    ->label('scope', 'public')
    ->label('docs', false)
    ->action(function ($response) {
        /** @var Appwrite\Utopia\Response $response */

        $response->json([
            'name' => APP_NAME,
            'short_name' => APP_NAME,
            'start_url' => '.',
            'url' => 'https://appwrite.io/',
            'display' => 'standalone',
            'background_color' => '#fff',
            'theme_color' => '#f02e65',
            'description' => 'End to end backend server for frontend and mobile apps. 👩‍💻👨‍💻',
            'icons' => [
                [
                    'src' => 'images/favicon.png',
                    'sizes' => '256x256',
                    'type' => 'image/png',
                ],
            ],
        ]);
    }, ['response']);

App::get('/robots.txt')
    ->desc('Robots.txt File')
    ->label('scope', 'public')
    ->label('docs', false)
    ->action(function ($response) {
        $template = new View(__DIR__.'/../views/general/robots.phtml');
        $response->text($template->render(false));
    }, ['response']);

App::get('/humans.txt')
    ->desc('Humans.txt File')
    ->label('scope', 'public')
    ->label('docs', false)
    ->action(function ($response) {
        $template = new View(__DIR__.'/../views/general/humans.phtml');
        $response->text($template->render(false));
    }, ['response']);

App::get('/.well-known/acme-challenge')
    ->desc('SSL Verification')
    ->label('scope', 'public')
    ->label('docs', false)
    ->action(function ($request, $response) {
        $base = \realpath(APP_STORAGE_CERTIFICATES);
        $path = \str_replace('/.well-known/acme-challenge/', '', $request->getParam('q'));
        $absolute = \realpath($base.'/.well-known/acme-challenge/'.$path);

        if (!$base) {
            throw new Exception('Storage error', 500);
        }

        if (!$absolute) {
            throw new Exception('Unknown path', 404);
        }

        if (!\substr($absolute, 0, \strlen($base)) === $base) {
            throw new Exception('Invalid path', 401);
        }

        if (!\file_exists($absolute)) {
            throw new Exception('Unknown path', 404);
        }

        $content = @\file_get_contents($absolute);

        if (!$content) {
            throw new Exception('Failed to get contents', 500);
        }

        $response->text($content);
    }, ['request', 'response']);

include_once __DIR__ . '/shared/api.php';
include_once __DIR__ . '/shared/web.php';

foreach (Config::getParam('services', []) as $service) {
    include_once $service['controller'];
}