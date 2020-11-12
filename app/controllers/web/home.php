<?php

use Appwrite\Spec\Spec;
use Appwrite\Specification\Format\OpenAPI3;
use Appwrite\Specification\Format\Swagger2;
use Appwrite\Specification\Specification;
use Appwrite\Template\Template;
use Utopia\App;
use Utopia\View;
use Utopia\Config\Config;
use Utopia\Exception;
use Utopia\Validator\WhiteList;
use Utopia\Validator\Range;

App::init(function ($layout) {
    /** @var Utopia\View $layout */

    $header = new View(__DIR__.'/../../views/home/comps/header.phtml');
    $footer = new View(__DIR__.'/../../views/home/comps/footer.phtml');

    $footer
        ->setParam('version', App::getEnv('_APP_VERSION', 'UNKNOWN'))
    ;

    $layout
        ->setParam('title', APP_NAME)
        ->setParam('description', '')
        ->setParam('class', 'home')
        ->setParam('platforms', Config::getParam('platforms'))
        ->setParam('header', [$header])
        ->setParam('footer', [$footer])
    ;
}, ['layout'], 'home');

App::shutdown(function ($response, $layout) {
    /** @var Appwrite\Utopia\Response $response */
    /** @var Utopia\View $layout */

    $response->html($layout->render());
}, ['response', 'layout'], 'home');

App::get('/')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($response) {
        /** @var Appwrite\Utopia\Response $response */

        $response->redirect('/auth/signin');
    }, ['response']);

App::get('/auth/signin')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/signin.phtml');

        $layout
            ->setParam('title', 'Sign In - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/signup')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */
        $page = new View(__DIR__.'/../../views/home/auth/signup.phtml');

        $layout
            ->setParam('title', 'Sign Up - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/recovery')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/recovery.phtml');

        $layout
            ->setParam('title', 'Password Recovery - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/confirm')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/confirm.phtml');

        $layout
            ->setParam('title', 'Account Confirmation - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/join')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/join.phtml');

        $layout
            ->setParam('title', 'Invitation - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/recovery/reset')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/recovery/reset.phtml');

        $layout
            ->setParam('title', 'Password Reset - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/auth/oauth2/success')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/oauth2.phtml');

        $layout
            ->setParam('title', APP_NAME)
            ->setParam('body', $page)
            ->setParam('header', [])
            ->setParam('footer', [])
        ;
    }, ['layout']);

App::get('/auth/oauth2/failure')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->action(function ($layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/home/auth/oauth2.phtml');

        $layout
            ->setParam('title', APP_NAME)
            ->setParam('body', $page)
            ->setParam('header', [])
            ->setParam('footer', [])
        ;
    }, ['layout']);

App::get('/error/:code')
    ->groups(['web', 'home'])
    ->label('permission', 'public')
    ->label('scope', 'home')
    ->param('code', null, new \Utopia\Validator\Numeric(), 'Valid status code number', false)
    ->action(function ($code, $layout) {
        /** @var Utopia\View $layout */

        $page = new View(__DIR__.'/../../views/error.phtml');

        $page
            ->setParam('code', $code)
        ;

        $layout
            ->setParam('title', 'Error'.' - '.APP_NAME)
            ->setParam('body', $page);
    }, ['layout']);

App::get('/specs/:format')
    ->groups(['web', 'home'])
    ->label('scope', 'public')
    ->label('docs', false)
    ->param('format', 'swagger2', new WhiteList(['swagger2', 'open-api3'], true), 'Spec format.', true)
    ->param('platform', APP_PLATFORM_CLIENT, new WhiteList([APP_PLATFORM_CLIENT, APP_PLATFORM_SERVER, APP_PLATFORM_CONSOLE], true), 'Choose target platform.', true)
    ->action(function ($format, $platform, $utopia, $request, $response) {
        /** @var Utopia\App $utopia */
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */

        $platforms = [
            'client' => APP_PLATFORM_CLIENT,
            'server' => APP_PLATFORM_SERVER,
            'console' => APP_PLATFORM_CONSOLE,
        ];

        $routes = [];
        $models = [];

        $keys = [
            APP_PLATFORM_CLIENT => [
                'Project' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Project',
                    'description' => 'Your project ID',
                    'in' => 'header',
                ],
                'Locale' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Locale',
                    'description' => '',
                    'in' => 'header',
                ],
            ],
            APP_PLATFORM_SERVER => [
                'Project' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Project',
                    'description' => 'Your project ID',
                    'in' => 'header',
                ],
                'Key' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Key',
                    'description' => 'Your secret API key',
                    'in' => 'header',
                ],
                'Locale' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Locale',
                    'description' => '',
                    'in' => 'header',
                ],
            ],
            APP_PLATFORM_CONSOLE => [
                'Project' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Project',
                    'description' => 'Your project ID',
                    'in' => 'header',
                ],
                'Key' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Key',
                    'description' => 'Your secret API key',
                    'in' => 'header',
                ],
                'Locale' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Locale',
                    'description' => '',
                    'in' => 'header',
                ],
                'Mode' => [
                    'type' => 'apiKey',
                    'name' => 'X-Appwrite-Mode',
                    'description' => '',
                    'in' => 'header',
                ],
            ],
        ];

        $security = [
            APP_PLATFORM_CLIENT => ['Project' => []],
            APP_PLATFORM_SERVER => ['Project' => [], 'Key' => []],
            APP_PLATFORM_CONSOLE => ['Project' => [], 'Key' => []],
        ];

        foreach ($utopia->getRoutes() as $key => $method) {
            foreach ($method as $route) { /** @var \Utopia\Route $route */
                if (!$route->getLabel('docs', true)) {
                    continue;
                }

                if ($route->getLabel('sdk.mock', false)) {
                    continue;
                }

                if (empty($route->getLabel('sdk.namespace', null))) {
                    continue;
                }

                if ($platform !== APP_PLATFORM_CONSOLE && !\in_array($platforms[$platform], $route->getLabel('sdk.platform', []))) {
                    continue;
                }

                $routes[] = $route;
                $model = $response->getModel($route->getLabel('sdk.response.model', 'none'));
                
                if($model) {
                    $models[$model->getType()] = $model;
                }
            }
        }

        $models = $response->getModels();

        foreach ($models as $key => $value) {
            if($platform !== APP_PLATFORM_CONSOLE && !$value->isPublic()) {
                unset($models[$key]);
            }
        }

        switch ($format) {
            case 'swagger2':
                $format = new Swagger2($utopia, $routes, $models, $keys[$platform], $security[$platform]);
                break;

            case 'open-api3':
                $format = new OpenAPI3($utopia, $routes, $models, $keys[$platform], $security[$platform]);
                break;
            
            default:
                throw new Exception('Format not found', 404);
                break;
        }

        $specs = new Specification($format);
        
        $format
            ->setParam('name', APP_NAME)
            ->setParam('description', 'Appwrite backend as a service cuts up to 70% of the time and costs required for building a modern application. We abstract and simplify common development tasks behind a REST APIs, to help you develop your app in a fast and secure way. For full API documentation and tutorials go to [https://appwrite.io/docs](https://appwrite.io/docs)')
            ->setParam('endpoint', App::getEnv('_APP_HOME', $request->getProtocol().'://'.$request->getHostname()).'/v1')
            ->setParam('version', APP_VERSION_STABLE)
            ->setParam('terms', App::getEnv('_APP_HOME', $request->getProtocol().'://'.$request->getHostname()).'/policy/terms')
            ->setParam('support.email', App::getEnv('_APP_SYSTEM_EMAIL_ADDRESS', APP_EMAIL_TEAM))
            ->setParam('support.url', App::getEnv('_APP_HOME', $request->getProtocol().'://'.$request->getHostname()).'/support')
            ->setParam('contact.name', APP_NAME.' Team')
            ->setParam('contact.email', App::getEnv('_APP_SYSTEM_EMAIL_ADDRESS', APP_EMAIL_TEAM))
            ->setParam('contact.url', App::getEnv('_APP_HOME', $request->getProtocol().'://'.$request->getHostname()).'/support')
            ->setParam('license.name', 'BSD-3-Clause')
            ->setParam('license.url', 'https://raw.githubusercontent.com/appwrite/appwrite/master/LICENSE')
            ->setParam('docs.description', 'Full API docs, specs and tutorials')
            ->setParam('docs.url', App::getEnv('_APP_HOME', $request->getProtocol().'://'.$request->getHostname()).'/docs')
        ;

        $response
            ->json($specs->parse());
    }, ['utopia', 'request', 'response']);