<?php

return [
    '/' => [
        'key' => 'homepage',
        'name' => 'Homepage',
        'subtitle' => '',
        'controller' => 'web/home.php',
        'sdk' => false,
        'docs' => false,
        'tests' => false,
        'optional' => false,
    ],
    'console' => [
        'key' => 'console',
        'name' => 'Console',
        'controller' => 'web/console.php',
        'sdk' => false,
        'docs' => false,
        'tests' => false,
        'optional' => false,
    ],
    'account' => [
        'key' => 'account',
        'name' => 'Account',
        'subtitle' => 'The Account service allows you to authenticate and manage a user account.',
        'description' => '/docs/services/account.md',
        'controller' => 'api/account.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'avatars' => [
        'key' => 'avatars',
        'name' => 'Avatars',
        'subtitle'=> 'The Avatars service aims to help you complete everyday tasks related to your app image, icons, and avatars.',
        'description' => '/docs/services/avatars.md',
        'controller' => 'api/avatars.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'database' => [
        'key' => 'database',
        'name' => 'Database',
        'subtitle' => 'The Database service allows you to create structured collections of documents, query and filter lists of documents',
        'description' => '/docs/services/database.md',
        'controller' => 'api/database.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'locale' => [
        'key' => 'locale',
        'name' => 'Locale',
        'subtitle' => 'The Locale service allows you to customize your app based on your users\' location.',
        'description' => '/docs/services/locale.md',
        'controller' => 'api/locale.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'health' => [
        'key' => 'health',
        'name' => 'Health',
        'subtitle' => 'The Health service allows you to both validate and monitor your Appwrite server\'s health.',
        'description' => '/docs/services/health.md',
        'controller' => 'api/health.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'projects' => [
        'key' => 'projects',
        'name' => 'Projects',
        'subtitle' => 'The Project service allows you to manage all the projects in your Appwrite server.',
        'controller' => 'api/projects.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => false,
    ],
    'storage' => [
        'key' => 'storage',
        'name' => 'Storage',
        'subtitle' => 'The Storage service allows you to manage your project files.',
        'description' => '/docs/services/storage.md',
        'controller' => 'api/storage.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'teams' => [
        'key' => 'teams',
        'name' => 'Teams',
        'subtitle' => 'The Teams service allows you to group users of your project and to enable them to share read and write access to your project resources',
        'description' => '/docs/services/teams.md',
        'controller' => 'api/teams.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'users' => [
        'key' => 'users',
        'name' => 'Users',
        'subtitle' => 'The Users service allows you to manage your project users.',
        'description' => '/docs/services/users.md',
        'controller' => 'api/users.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'functions' => [
        'key' => 'functions',
        'name' => 'Functions',
        'subtitle' => 'The Functions Service allows you view, create and manage your Cloud Functions.',
        'description' => '/docs/services/functions.md',
        'controller' => 'api/functions.php',
        'sdk' => true,
        'docs' => true,
        'tests' => false,
        'optional' => true,
    ],
    'mock' => [
        'key' => 'mock',
        'name' => 'Mock',
        'subtitle' => '',
        'description' => '',
        'controller' => 'mock.php',
        'sdk' => false,
        'docs' => false,
        'tests' => true,
        'optional' => false,
    ],
    'graphql' => [
        'key' => 'graphql',
        'name' => 'GraphQL',
        'subtitle' => 'Appwrite\'s GraphQL Endpoint',
        'description' => 'GraphQL Endpoint',
        'controller' => 'api/graphql.php',
        'sdk' => false,
        'docs' => false,
        'tests' => true,
        'optional' => false,
    ],
];
