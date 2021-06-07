<?php

const APP_PLATFORM_SERVER = 'server';
const APP_PLATFORM_CLIENT = 'client';
const APP_PLATFORM_CONSOLE = 'console';

return [
    APP_PLATFORM_CLIENT => [
        'key' => APP_PLATFORM_CLIENT,
        'name' => 'Client',
        'description' => 'Client libraries for integrating with Appwrite to build client-based applications and websites. Read the [getting started for web](/docs/getting-started-for-web) or [getting started for Flutter](/docs/getting-started-for-flutter) tutorials to start building your first application.',
        'enabled' => true,
        'beta' => false,
        'languages' => [ // TODO change key to 'sdks'
            [
                'key' => 'web',
                'name' => 'Web',
                'version' => '3.1.0',
                'url' => 'https://github.com/appwrite/sdk-for-web',
                'package' => 'https://www.npmjs.com/package/appwrite',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'javascript',
                'source' => \realpath(__DIR__ . '/../sdks/client-web'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-web.git',
                'gitRepoName' => 'sdk-for-web',
                'gitUserName' => 'appwrite',
                'demos' => [
                    [
                        'icon' => 'react.svg',
                        'name' => 'Todo App with React JS',
                        'description' => 'A simple Todo app that uses both the Appwrite account and database APIs.',
                        'source' => 'https://github.com/appwrite/todo-with-react',
                        'url' => 'https://appwrite-todo-with-react.vercel.app/',
                    ],
                    [
                        'icon' => 'vue.svg',
                        'name' => 'Todo App with Vue JS',
                        'description' => 'A simple Todo app that uses both the Appwrite account and database APIs.',
                        'source' => 'https://github.com/appwrite/todo-with-vue',
                        'url' => 'https://appwrite-todo-with-vue.vercel.app/',
                    ],
                    [
                        'icon' => 'angular.svg',
                        'name' => 'Todo App with Angular.js',
                        'description' => 'A simple Todo app that uses both the Appwrite account and database APIs.',
                        'source' => 'https://github.com/appwrite/todo-with-angular',
                        'url' => 'https://appwrite-todo-with-angular.vercel.app/',
                    ],
                    [
                        'icon' => 'svelte.svg',
                        'name' => 'Todo App with Svelte',
                        'description' => 'A simple Todo app that uses both the Appwrite account and database APIs.',
                        'source' => 'https://github.com/appwrite/todo-with-svelte',
                        'url' => 'https://appwrite-todo-with-svelte.vercel.app/',
                    ],
                ]
            ],
            [
                'key' => 'flutter',
                'name' => 'Flutter',
                'version' => '0.6.4',
                'url' => 'https://github.com/appwrite/sdk-for-flutter',
                'package' => 'https://pub.dev/packages/appwrite',
                'enabled' => true,
                'beta' => true,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'dart',
                'source' => \realpath(__DIR__ . '/../sdks/client-flutter'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-flutter.git',
                'gitRepoName' => 'sdk-for-flutter',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'swift',
                'name' => 'Swift',
                'url' => '',
                'package' => '',
                'enabled' => false,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'swift',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-swift.git',
                'gitRepoName' => 'sdk-for-swift',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'objective-c',
                'name' => 'Objective C',
                'url' => '',
                'package' => '',
                'enabled' => false,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => '',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-objective-c.git',
                'gitRepoName' => 'sdk-for-objective-c',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'kotlin',
                'name' => 'Kotlin',
                'url' => '',
                'package' => '',
                'enabled' => false,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'kotlin',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-kotlin.git',
                'gitRepoName' => 'sdk-for-kotlin',
                'gitUserName' => 'appwrite',
            ],
            // [
            //     'key' => 'java',
            //     'name' => 'Java',
            //     'url' => '',
            //     'enabled' => false,
            //     'beta' => false,
            //     'dev' => false,
            //     'hidden' => false,
            //     'family' => APP_PLATFORM_CLIENT,
            //     'prism' => 'java',
            //     'source' => false,
            //     'gitUrl' => 'git@github.com:appwrite/sdk-for-java.git',
            //     'gitRepoName' => 'sdk-for-java',
            //     'gitUserName' => 'appwrite',
            // ],
        ],
    ],
    
    APP_PLATFORM_CONSOLE => [
        'key' => APP_PLATFORM_CONSOLE,
        'name' => 'Console',
        'enabled' => false,
        'beta' => false,
        'languages' => [ // TODO change key to 'sdks'
            [
                'key' => 'web',
                'name' => 'Console',
                'version' => '2.0.0',
                'url' => 'https://github.com/appwrite/sdk-for-console',
                'package' => '',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => true,
                'family' => APP_PLATFORM_CONSOLE,
                'prism' => 'console',
                'source' => \realpath(__DIR__ . '/../sdks/console-web'),
                'gitUrl' => null,
                'gitRepoName' => 'sdk-for-console',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],

    APP_PLATFORM_SERVER => [
        'key' => APP_PLATFORM_SERVER,
        'name' => 'Server',
        'description' => 'Libraries for integrating with Appwrite to build server side integrations. Read the [getting started for server](/docs/getting-started-for-server) tutorial to start building your first server integration.',
        'enabled' => true,
        'beta' => false,
        'languages' => [ // TODO change key to 'sdks'
            [
                'key' => 'nodejs',
                'name' => 'Node.js',
                'version' => '2.3.0',
                'url' => 'https://github.com/appwrite/sdk-for-node',
                'package' => 'https://www.npmjs.com/package/node-appwrite',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'javascript',
                'source' => \realpath(__DIR__ . '/../sdks/server-nodejs'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-node.git',
                'gitRepoName' => 'sdk-for-node',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'deno',
                'name' => 'Deno',
                'version' => '0.2.2',
                'url' => 'https://github.com/appwrite/sdk-for-deno',
                'package' => 'https://deno.land/x/appwrite',
                'enabled' => true,
                'beta' => true,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'typescript',
                'source' => \realpath(__DIR__ . '/../sdks/server-deno'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-deno.git',
                'gitRepoName' => 'sdk-for-deno',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'php',
                'name' => 'PHP',
                'version' => '2.1.2',
                'url' => 'https://github.com/appwrite/sdk-for-php',
                'package' => 'https://packagist.org/packages/appwrite/appwrite',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'php',
                'source' => \realpath(__DIR__ . '/../sdks/server-php'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-php.git',
                'gitRepoName' => 'sdk-for-php',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'python',
                'name' => 'Python',
                'version' => '0.3.0',
                'url' => 'https://github.com/appwrite/sdk-for-python',
                'package' => 'https://pypi.org/project/appwrite/',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'python',
                'source' => \realpath(__DIR__ . '/../sdks/server-python'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-python.git',
                'gitRepoName' => 'sdk-for-python',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'ruby',
                'name' => 'Ruby',
                'version' => '2.2.0',
                'url' => 'https://github.com/appwrite/sdk-for-ruby',
                'package' => 'https://rubygems.org/gems/appwrite',
                'enabled' => true,
                'beta' => false,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'ruby',
                'source' => \realpath(__DIR__ . '/../sdks/server-ruby'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-ruby.git',
                'gitRepoName' => 'sdk-for-ruby',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'go',
                'name' => 'Go',
                'version' => '0.0.7',
                'url' => 'https://github.com/appwrite/sdk-for-go',
                'package' => '',
                'enabled' => false,
                'beta' => true,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'go',
                'source' => \realpath(__DIR__ . '/../sdks/server-go'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-go.git',
                'gitRepoName' => 'sdk-for-go',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'java',
                'name' => 'Java',
                'version' => '0.0.2',
                'url' => 'https://github.com/appwrite/sdk-for-java',
                'package' => '',
                'enabled' => false,
                'beta' => true,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'java',
                'source' => \realpath(__DIR__ . '/../sdks/server-java'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-java.git',
                'gitRepoName' => 'sdk-for-java',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'dotnet',
                'name' => '.NET',
                'version' => '0.3.0',
                'url' => 'https://github.com/appwrite/sdk-for-dotnet',
                'package' => 'https://www.nuget.org/packages/Appwrite',
                'enabled' => true,
                'beta' => true,
                'dev' => true,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'csharp',
                'source' => \realpath(__DIR__ . '/../sdks/server-dotnet'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-dotnet.git',
                'gitRepoName' => 'sdk-for-dotnet',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'dart',
                'name' => 'Dart',
                'version' => '0.6.3',
                'url' => 'https://github.com/appwrite/sdk-for-dart',
                'package' => 'https://pub.dev/packages/dart_appwrite',
                'enabled' => true,
                'beta' => true,
                'dev' => false,
                'hidden' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'dart',
                'source' => \realpath(__DIR__ . '/../sdks/server-dart'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-dart.git',
                'gitRepoName' => 'sdk-for-dart',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'cli',
                'name' => 'Command Line',
                'version' => '0.10.0',
                'url' => 'https://github.com/appwrite/sdk-for-cli',
                'package' => 'https://github.com/appwrite/sdk-for-cli',
                'enabled' => true,
                'beta' => true,
                'dev' => false,
                'hidden' => true,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'bash',
                'source' => \realpath(__DIR__ . '/../sdks/server-cli'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-cli.git',
                'gitRepoName' => 'sdk-for-cli',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],
];
