<?php

const APP_PLATFORM_WEB = 'web';
const APP_PLATFORM_IOS = 'ios';
const APP_PLATFORM_ANDROID = 'android';
const APP_PLATFORM_UNITY = 'unity';
const APP_PLATFORM_FLUTTER = 'flutter';

const APP_PLATFORM_SERVER = 'server';
const APP_PLATFORM_CLIENT = 'client';
const APP_PLATFORM_CONSOLE = 'console';

return [
    APP_PLATFORM_WEB => [
        'key' => APP_PLATFORM_WEB,
        'name' => 'Web',
        'description' => 'Client libraries for integrating with '.APP_NAME.' to build web-based applications and websites. Read the [getting started for web](/docs/getting-started-for-web) tutorial to start building your first web application.',
        'enabled' => true,
        'beta' => false,
        'languages' => [
            [
                'key' => 'javascript',
                'name' => 'JavaScript',
                'version' => '1.0.29',
                'url' => 'https://github.com/appwrite/sdk-for-js',
                'enabled' => true,
                'beta' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'javascript',
                'source' => realpath(__DIR__ . '/../sdks/web-javascript'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-js.git',
                'gitRepoName' => 'sdk-for-js',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'typescript',
                'name' => 'TypeScript',
                'url' => '',
                'enabled' => false,
                'beta' => true,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'typescript',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-typescript.git',
                'gitRepoName' => 'sdk-for-typescript',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],
    
    APP_PLATFORM_IOS => [
        'key' => APP_PLATFORM_IOS,
        'name' => 'iOS',
        'description' => 'Client libraries for integrating with '.APP_NAME.' to build iOS applications. Read the [getting started for iOS](/docs/getting-started-for-ios) tutorial to start building your first iOS application.',
        'enabled' => false,
        'beta' => false,
        'languages' => [
            [
                'key' => 'swift',
                'name' => 'Swift',
                'url' => '',
                'enabled' => false,
                'beta' => false,
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
                'enabled' => false,
                'beta' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => '',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-objective-c.git',
                'gitRepoName' => 'sdk-for-objective-c',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],

    APP_PLATFORM_ANDROID => [
        'key' => APP_PLATFORM_ANDROID,
        'name' => 'Android',
        'description' => 'Client libraries for integrating with '.APP_NAME.' to build Android applications. Read the [getting started for Android](/docs/getting-started-for-android) tutorial to start building your first Android application.',
        'enabled' => false,
        'beta' => false,
        'languages' => [
            [
                'key' => 'kotlin',
                'name' => 'Kotlin',
                'url' => '',
                'enabled' => false,
                'beta' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'kotlin',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-kotlin.git',
                'gitRepoName' => 'sdk-for-kotlin',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'java',
                'name' => 'Java',
                'url' => '',
                'enabled' => false,
                'beta' => false,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'java',
                'source' => false,
                'gitUrl' => 'git@github.com:appwrite/sdk-for-java.git',
                'gitRepoName' => 'sdk-for-java',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],

    APP_PLATFORM_FLUTTER => [
        'key' => APP_PLATFORM_FLUTTER,
        'name' => 'Flutter',
        'description' => 'Client libraries for integrating with '.APP_NAME.' to build cross-platform Flutter applications. Read the [getting started for Flutter](/docs/getting-started-for-flutter) tutorial to start building your first Flutter application.',
        'enabled' => false,
        'beta' => true,
        'languages' => [
            [
                'key' => 'dart',
                'name' => 'Dart',
                'version' => '0.0.10',
                'url' => 'https://github.com/appwrite/sdk-for-dart',
                'enabled' => true,
                'beta' => true,
                'family' => APP_PLATFORM_CLIENT,
                'prism' => 'dart',
                'source' => realpath(__DIR__ . '/../sdks/flutter-dart'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-dart.git',
                'gitRepoName' => 'sdk-for-dart',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],

    APP_PLATFORM_CONSOLE => [
        'key' => APP_PLATFORM_CONSOLE,
        'name' => 'Console',
        'enabled' => false,
        'beta' => false,
        'languages' => [
            [
                'key' => 'javascript',
                'name' => 'JS',
                'version' => '1.0.0',
                'url' => 'https://github.com/appwrite/sdk-for-console',
                'enabled' => true,
                'beta' => false,
                'family' => APP_PLATFORM_CONSOLE,
                'prism' => 'console',
                'source' => realpath(__DIR__ . '/../sdks/console-javascript'),
                'gitUrl' => null,
                'gitRepoName' => 'sdk-for-console',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],

    APP_PLATFORM_SERVER => [
        'key' => APP_PLATFORM_SERVER,
        'name' => 'Server',
        'description' => 'Libraries for integrating with '.APP_NAME.' to build server side integrations. Read the [getting started for server](/docs/getting-started-for-server) tutorial to start building your first server integration.',
        'enabled' => true,
        'beta' => false,
        'languages' => [
            [
                'key' => 'nodejs',
                'name' => 'Node.js',
                'version' => '1.0.32',
                'url' => 'https://github.com/appwrite/sdk-for-node',
                'enabled' => true,
                'beta' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'javascript',
                'source' => realpath(__DIR__ . '/../sdks/server-nodejs'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-node.git',
                'gitRepoName' => 'sdk-for-node',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'php',
                'name' => 'PHP',
                'version' => '1.0.17',
                'url' => 'https://github.com/appwrite/sdk-for-php',
                'enabled' => true,
                'beta' => false,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'php',
                'source' => realpath(__DIR__ . '/../sdks/server-php'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-php.git',
                'gitRepoName' => 'sdk-for-php',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'python',
                'name' => 'Python',
                'version' => '0.0.4',
                'url' => 'https://github.com/appwrite/sdk-for-python',
                'enabled' => true,
                'beta' => true,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'python',
                'source' => realpath(__DIR__ . '/../sdks/server-python'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-python.git',
                'gitRepoName' => 'sdk-for-python',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'ruby',
                'name' => 'Ruby',
                'version' => '1.0.9',
                'url' => 'https://github.com/appwrite/sdk-for-ruby',
                'enabled' => true,
                'beta' => true,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'ruby',
                'source' => realpath(__DIR__ . '/../sdks/server-ruby'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-ruby.git',
                'gitRepoName' => 'sdk-for-ruby',
                'gitUserName' => 'appwrite',
            ],
            [
                'key' => 'go',
                'name' => 'Go',
                'version' => '0.0.6',
                'url' => 'https://github.com/appwrite/sdk-for-go',
                'enabled' => true,
                'beta' => true,
                'family' => APP_PLATFORM_SERVER,
                'prism' => 'go',
                'source' => realpath(__DIR__ . '/../sdks/server-go'),
                'gitUrl' => 'git@github.com:appwrite/sdk-for-go.git',
                'gitRepoName' => 'sdk-for-go',
                'gitUserName' => 'appwrite',
            ],
        ],
    ],
    
];
