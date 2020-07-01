<?php

use Utopia\App;
use Utopia\Exception;
use Utopia\Abuse\Abuse;
use Utopia\Abuse\Adapters\TimeLimit;

App::init(function ($utopia, $request, $response, $register, $user, $project) {
    $route = $utopia->match($request);

    if (empty($project->getId()) && $route->getLabel('abuse-limit', 0) > 0) { // Abuse limit requires an active project scope
        throw new Exception('Missing or unknown project ID', 400);
    }

    /*
     * Abuse Check
     */
    $timeLimit = new TimeLimit($route->getLabel('abuse-key', 'url:{url},ip:{ip}'), $route->getLabel('abuse-limit', 0), $route->getLabel('abuse-time', 3600), function () use ($register) {
        return $register->get('db');
    });
    $timeLimit->setNamespace('app_'.$project->getId());
    $timeLimit
        ->setParam('{userId}', $user->getId())
        ->setParam('{userAgent}', $request->getServer('HTTP_USER_AGENT', ''))
        ->setParam('{ip}', $request->getIP())
        ->setParam('{url}', $request->getServer('HTTP_HOST', '').$route->getURL())
    ;

    //TODO make sure we get array here

    foreach ($request->getParams() as $key => $value) { // Set request params as potential abuse keys
        $timeLimit->setParam('{param-'.$key.'}', (\is_array($value)) ? \json_encode($value) : $value);
    }

    $abuse = new Abuse($timeLimit);

    if ($timeLimit->limit()) {
        $response
            ->addHeader('X-RateLimit-Limit', $timeLimit->limit())
            ->addHeader('X-RateLimit-Remaining', $timeLimit->remaining())
            ->addHeader('X-RateLimit-Reset', $timeLimit->time() + $route->getLabel('abuse-time', 3600))
        ;
    }

    if ($abuse->check() && App::getEnv('_APP_OPTIONS_ABUSE', 'enabled') !== 'disabled') {
        throw new Exception('Too many requests', 429);
    }
}, ['utopia', 'request', 'response', 'register', 'user', 'project'], 'api');