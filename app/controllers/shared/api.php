<?php

use Appwrite\Auth\Auth;
use Appwrite\Event\Event;
use Appwrite\Messaging\Adapter\Realtime;
use Utopia\App;
use Appwrite\Extend\Exception;
use Utopia\Abuse\Abuse;
use Utopia\Abuse\Adapters\TimeLimit;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Query;
use Utopia\Storage\Device\DOSpaces;
use Utopia\Database\Validator\Authorization;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\S3;
use Utopia\Storage\Storage;

App::init(function ($utopia, $request, $response, $project, $user, $events, $audits, $mails, $usage, $deletes, $database, $dbForProject, $mode) {
    /** @var Utopia\App $utopia */
    /** @var Appwrite\Utopia\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Utopia\Database\Document $project */
    /** @var Utopia\Database\Document $user */
    /** @var Utopia\Registry\Registry $register */
    /** @var Appwrite\Event\Event $events */
    /** @var Appwrite\Event\Audit $audits */
    /** @var Appwrite\Event\Mail $mails */
    /** @var Appwrite\Stats\Stats $usage */
    /** @var Appwrite\Event\Event $deletes */
    /** @var Appwrite\Event\Event $database */
    /** @var Appwrite\Event\Event $functions */
    /** @var Utopia\Database\Database $dbForProject */

    $route = $utopia->match($request);

    if ($project->isEmpty() && $route->getLabel('abuse-limit', 0) > 0) { // Abuse limit requires an active project scope
        throw new Exception('Missing or unknown project ID', 400, Exception::PROJECT_UNKNOWN);
    }

    /*
     * Abuse Check
     */
    $abuseKeyLabel = $route->getLabel('abuse-key', 'url:{url},ip:{ip}');
    $timeLimitArray = [];

    $abuseKeyLabel = (!is_array($abuseKeyLabel)) ? [$abuseKeyLabel] : $abuseKeyLabel;

    foreach ($abuseKeyLabel as $abuseKey) {
        $timeLimit = new TimeLimit($abuseKey, $route->getLabel('abuse-limit', 0), $route->getLabel('abuse-time', 3600), $dbForProject);
        $timeLimit
            ->setParam('{userId}', $user->getId())
            ->setParam('{userAgent}', $request->getUserAgent(''))
            ->setParam('{ip}', $request->getIP())
            ->setParam('{url}', $request->getHostname().$route->getPath());
        $timeLimitArray[] = $timeLimit;
    }

    $closestLimit = null;

    $roles = Authorization::getRoles();
    $isPrivilegedUser = Auth::isPrivilegedUser($roles);
    $isAppUser = Auth::isAppUser($roles);

    foreach ($timeLimitArray as $timeLimit) {
        foreach ($request->getParams() as $key => $value) { // Set request params as potential abuse keys
            if(!empty($value)) {
                $timeLimit->setParam('{param-'.$key.'}', (\is_array($value)) ? \json_encode($value) : $value);
            }
        }

        $abuse = new Abuse($timeLimit);

        if ($timeLimit->limit() && ($timeLimit->remaining() < $closestLimit || is_null($closestLimit))) {
            $closestLimit = $timeLimit->remaining();
            $response
                ->addHeader('X-RateLimit-Limit', $timeLimit->limit())
                ->addHeader('X-RateLimit-Remaining', $timeLimit->remaining())
                ->addHeader('X-RateLimit-Reset', $timeLimit->time() + $route->getLabel('abuse-time', 3600))
            ;
        }

        if ((App::getEnv('_APP_OPTIONS_ABUSE', 'enabled') !== 'disabled' // Route is rate-limited
        && $abuse->check()) // Abuse is not disabled
        && (!$isAppUser && !$isPrivilegedUser)) // User is not an admin or API key
        {
            throw new Exception('Too many requests', 429, Exception::GENERAL_RATE_LIMIT_EXCEEDED);
        }
    }

    /*
     * Background Jobs
     */
    $events
        ->setEvent($route->getLabel('event', ''))
        ->setProject($project)
        ->setUser($user)
    ;

    $mails
        ->setProject($project)
        ->setUser($user)
    ;

    $audits
        ->setMode($mode)
        ->setUserAgent($request->getUserAgent(''))
        ->setIP($request->getIP())
        ->setEvent($route->getLabel('event', ''))
        ->setProject($project)
        ->setUser($user)
    ;

    $usage
        ->setParam('projectId', $project->getId())
        ->setParam('httpRequest', 1)
        ->setParam('httpUrl', $request->getHostname().$request->getURI())
        ->setParam('httpMethod', $request->getMethod())
        ->setParam('httpPath', $route->getPath())
        ->setParam('networkRequestSize', 0)
        ->setParam('networkResponseSize', 0)
        ->setParam('storage', 0)
    ;

    $deletes->setProject($project);
    $database->setProject($project);
}, ['utopia', 'request', 'response', 'project', 'user', 'events', 'audits', 'mails', 'usage', 'deletes', 'database', 'dbForProject', 'mode'], 'api');

App::init(function ($utopia, $request, $project) {
    /** @var Utopia\App $utopia */
    /** @var Appwrite\Utopia\Request $request */
    /** @var Utopia\Database\Document $project */

    $route = $utopia->match($request);

    $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::getRoles());
    $isAppUser = Auth::isAppUser(Authorization::getRoles());

    if($isAppUser || $isPrivilegedUser) { // Skip limits for app and console devs
        return;
    }

    $auths = $project->getAttribute('auths', []);
    switch ($route->getLabel('auth.type', '')) {
        case 'emailPassword':
            if(($auths['emailPassword'] ?? true) === false) {
                throw new Exception('Email / Password authentication is disabled for this project', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            }
            break;

        case 'magic-url':
            if($project->getAttribute('usersAuthMagicURL', true) === false) {
                throw new Exception('Magic URL authentication is disabled for this project', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            }
            break;

        case 'anonymous':
            if(($auths['anonymous'] ?? true) === false) {
                throw new Exception('Anonymous authentication is disabled for this project', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            }
            break;

        case 'invites':
            if(($auths['invites'] ?? true) === false) {
                throw new Exception('Invites authentication is disabled for this project', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            }
            break;

        case 'jwt':
            if(($auths['JWT'] ?? true) === false) {
                throw new Exception('JWT authentication is disabled for this project', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            }
            break;

        default:
            throw new Exception('Unsupported authentication route', 501, Exception::USER_AUTH_METHOD_UNSUPPORTED);
            break;
    }

}, ['utopia', 'request', 'project'], 'auth');

App::shutdown(function ($utopia, $request, $response, $project, $events, $audits, $usage, $deletes, $database, $mode, $dbForProject) {
    /** @var Utopia\App $utopia */
    /** @var Appwrite\Utopia\Request $request */
    /** @var Appwrite\Utopia\Response $response */
    /** @var Utopia\Database\Document $project */
    /** @var Appwrite\Event\Event $events */
    /** @var Appwrite\Event\Audit $audits */
    /** @var Appwrite\Stats\Stats $usage */
    /** @var Appwrite\Event\Event $deletes */
    /** @var Appwrite\Event\Database $database */
    /** @var bool $mode */
    /** @var Utopia\Database\Database $dbForProject */

    if (!empty($events->getEvent())) {
        /**
         * Trigger functions.
         */
        $events
            ->setClass(Event::FUNCTIONS_CLASS_NAME)
            ->setQueue(Event::FUNCTIONS_QUEUE_NAME)
            ->setPayload($response->getPayload())
            ->trigger();

        /**
         * Trigger webhooks.
         */
        $events
            ->setClass(Event::WEBHOOK_CLASS_NAME)
            ->setQueue(Event::WEBHOOK_QUEUE_NAME)
            ->setPayload($response->getPayload())
            ->trigger();

        /**
         * Trigger realtime.
         */
        if ($project->getId() !== 'console') {
            $allEvents = Event::generateEvents($events->getEvent(), $events->getParams());
            $payload = new Document($response->getPayload());
            $trigger = $events->getTrigger() ?? false;

            $collection = ($trigger && $trigger->getCollection() === 'collections') ? $trigger : null;
            $bucket = ($trigger && $trigger->getCollection() === 'buckets') ? $trigger : null;

            $target = Realtime::fromPayload(
                event: $allEvents[0],
                payload: $payload,
                project: $project,
                collection: $collection,
                bucket: $bucket,
            );

            Realtime::send(
                $target['projectId'] ?? $project->getId(),
                $response->getPayload(),
                $allEvents[0],
                $target['channels'],
                $target['roles'],
                [
                    'permissionsChanged' => $target['permissionsChanged'], 
                    'userId' => $events->getParam('userId')
                ]
            );
        }
    }

    if (!empty($audits->getResource())) {
        foreach ($events->getParams() as $key => $value) {
            $audits->setParam($key, $value);
        }
        $audits->trigger();
    }

    if (!empty($deletes->getPayload())) {
        $deletes->trigger();
    }

    if (!empty($database->getType())) {
        $database->trigger();
    }

    $route = $utopia->match($request);
    if (App::getEnv('_APP_USAGE_STATS', 'enabled') == 'enabled' 
        && $project->getId()
        && $mode !== APP_MODE_ADMIN // TODO: add check to make sure user is admin
        && !empty($route->getLabel('sdk.namespace', null))) { // Don't calculate console usage on admin mode

        // $usage
        //     ->setParam('networkRequestSize', $request->getSize() + $usage->getParam('storage'))
        //     ->setParam('networkResponseSize', $response->getSize())
        //     ->submit()
        // ;
    }

}, ['utopia', 'request', 'response', 'project', 'events', 'audits', 'usage', 'deletes', 'database', 'mode', 'dbForProject'], 'api');