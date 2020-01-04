<?php

return [
    '/' => [
        'name' => 'Homepage',
        'controller' => 'controllers/web/home.php',
        'sdk' => false,
        'tests' => false,
    ],
    'console/' => [
        'name' => 'Console',
        'controller' => 'controllers/web/console.php',
        'sdk' => false,
        'tests' => false,
    ],
    'v1/account' => [
        'name' => 'Account',
        'description' => '/docs/services/account.md',
        'controller' => 'controllers/api/account.php',
        'sdk' => true,
        'tests' => false,
    ],
    // 'v1/auth' => [ // Add to docs later: You can also learn how to [configure support for our supported OAuth providers](/docs/oauth)
    //     'name' => 'Auth',
    //     'description' => '/docs/services/auth.md',
    //     'controller' => 'controllers/api/auth.php',
    //     'sdk' => true,
    //     'tests' => false,
    // ],
    'v1/avatars' => [
        'name' => 'Avatars',
        'description' => '/docs/services/avatars.md',
        'controller' => 'controllers/api/avatars.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/database' => [
        'name' => 'Database',
        'description' => '/docs/services/database.md',
        'controller' => 'controllers/api/database.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/locale' => [
        'name' => 'Locale',
        'description' => '/docs/services/locale.md',
        'controller' => 'controllers/api/locale.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/health' => [
        'name' => 'Health',
        'controller' => 'controllers/api/health.php',
        'sdk' => false,
        'tests' => false,
    ],
    'v1/projects' => [
        'name' => 'Projects',
        'controller' => 'controllers/api/projects.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/storage' => [
        'name' => 'Storage',
        'description' => '/docs/services/storage.md',
        'controller' => 'controllers/api/storage.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/teams' => [
        'name' => 'Teams',
        'description' => '/docs/services/teams.md',
        'controller' => 'controllers/api/teams.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/users' => [
        'name' => 'Users',
        'description' => '/docs/services/users.md',
        'controller' => 'controllers/api/users.php',
        'sdk' => true,
        'tests' => false,
    ],
    'v1/mock' => [
        'name' => 'Mock',
        'description' => '',
        'controller' => 'controllers/mock.php',
        'sdk' => false,
        'tests' => true,
    ],
    // 'v1/keys' => [
    //     'name' => 'Keys',
    //     'description' => '',
    //     'controller' => 'controllers/api/keys.php',
    //     'sdk' => true,
    //     'tests' => false,
    // ],
    // 'v1/platforms' => [
    //     'name' => 'Platforms',
    //     'description' => '',
    //     'controller' => 'controllers/api/platforms.php',
    //     'sdk' => true,
    //     'tests' => false,
    // ],
    // 'v1/tasks' => [
    //     'name' => 'Tasks',
    //     'description' => '',
    //     'controller' => 'controllers/api/tasks.php',
    //     'sdk' => true,
    //     'tests' => false,
    // ],
    // 'v1/webhooks' => [
    //     'name' => 'Webhooks',
    //     'description' => '',
    //     'controller' => 'controllers/api/webhooks.php',
    //     'sdk' => true,
    //     'tests' => false,
    // ],
    'v1/graphql' => [
        'name' => 'GraphQL',
        'description' => 'GraphQL Endpoint',
        'controller' => 'controllers/api/graphql.php',
        'sdk' => false,
        'tests' => false,
    ],
];
