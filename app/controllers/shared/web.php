<?php

use Utopia\App;
use Utopia\Config\Config;

App::init(function ($utopia, $request, $response, $layout) {
    /** @var Utopia\App $utopia */
    /** @var Utopia\Request $request */
    /** @var Utopia\Response $response */
    /** @var Utopia\View $layout */

    /* AJAX check  */
    if (!empty($request->getQuery('version', ''))) {
        $layout->setPath(__DIR__.'/../../views/layouts/empty.phtml');
    }
    
    $layout
        ->setParam('title', APP_NAME)
        ->setParam('protocol', $request->getProtocol())
        ->setParam('domain', $request->getHostname())
        ->setParam('home', App::getEnv('_APP_HOME'))
        ->setParam('setup', App::getEnv('_APP_SETUP'))
        ->setParam('class', 'unknown')
        ->setParam('icon', '/images/favicon.png')
        ->setParam('roles', [
            ['type' => 'owner', 'label' => 'Owner'],
            ['type' => 'developer', 'label' => 'Developer'],
            ['type' => 'admin', 'label' => 'Admin'],
        ])
        ->setParam('environments', Config::getParam('environments'))
        ->setParam('mode', App::getMode())
    ;

    $time = (60 * 60 * 24 * 45); // 45 days cache

    $response
        ->addHeader('Cache-Control', 'public, max-age='.$time)
        ->addHeader('Expires', \date('D, d M Y H:i:s', \time() + $time).' GMT') // 45 days cache
        ->addHeader('X-Frame-Options', 'SAMEORIGIN') // Avoid console and homepage from showing in iframes
        ->addHeader('X-UA-Compatible', 'IE=Edge') // Deny IE browsers from going into quirks mode
    ;

    $route = $utopia->match($request);
    $scope = $route->getLabel('scope', '');
    $layout
        ->setParam('version', App::getEnv('_APP_VERSION', 'UNKNOWN'))
        ->setParam('isDev', App::isDevelopment())
        ->setParam('class', $scope)
    ;
}, ['utopia', 'request', 'response', 'layout'], 'web');
