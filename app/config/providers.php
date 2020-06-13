<?php

return [ // Ordered by ABC.
    'amazon' => [
        'developers' => 'https://developer.amazon.com/apps-and-games/services-and-apis',
        'icon' => 'icon-amazon',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'apple' => [
        'developers' => 'https://developer.apple.com/',
        'icon' => 'icon-apple',
        'enabled' => true,
        'form' => 'apple.phtml', // Perperation for adding ability to customized OAuth UI forms, currently handled hardcoded.
        'beta' => true,
        'mock' => false,
    ],
    'bitbucket' => [
        'developers' => 'https://developer.atlassian.com/bitbucket',
        'icon' => 'icon-bitbucket',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'bitly' => [
        'developers' => 'https://dev.bitly.com/v4_documentation.html',
        'icon' => 'icon-bitly',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false
    ],
    'discord' => [
        'developers' => 'https://discordapp.com/developers/docs/topics/oauth2',
        'icon' => 'icon-discord',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'dropbox' => [
        'developers' => 'https://www.dropbox.com/developers/documentation',
        'icon' => 'icon-dropbox',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'facebook' => [
        'developers' => 'https://developers.facebook.com/',
        'icon' => 'icon-facebook',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'github' => [
        'developers' => 'https://developer.github.com/',
        'icon' => 'icon-github-circled',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'gitlab' => [
        'developers' => 'https://docs.gitlab.com/ee/api/',
        'icon' => 'icon-gitlab',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'google' => [
        'developers' => 'https://developers.google.com/',
        'icon' => 'icon-google',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'linkedin' => [
        'developers' => 'https://developer.linkedin.com/',
        'icon' => 'icon-linkedin',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'microsoft' => [
        'developers' => 'https://developer.microsoft.com/en-us/',
        'icon' => 'icon-windows',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'paypal' => [
        'developers' => 'https://developer.paypal.com/docs/api/overview/',
        'icon' => 'icon-paypal',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false
    ],
    'salesforce' => [
        'developers' => 'https://developer.salesforce.com/docs/',
        'icon' => 'icon-salesforce',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'slack' => [
        'developers' => 'https://api.slack.com/',
        'icon' => 'icon-slack',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'spotify' => [
        'developers' => 'https://developer.spotify.com/documentation/general/guides/authorization-guide/',
        'icon' => 'icon-spotify',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'twitch' => [
        'developers' => 'https://dev.twitch.tv/docs/authentication',
        'icon' => 'icon-twitch',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'vk' => [
        'developers' => 'https://vk.com/dev',
        'icon' => 'icon-vk',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'yahoo' => [
        'developers' => 'https://developer.yahoo.com/oauth2/guide/flows_authcode/',
        'icon' => 'icon-yahoo',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    'yandex' => [
        'developers' => 'https://tech.yandex.com/oauth/',
        'icon' => 'icon-yandex',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => false,
    ],
    // 'instagram' => [
    //     'developers' => 'https://www.instagram.com/developer/',
    //     'icon' => 'icon-instagram',
    //     'enabled' => false,
    //     'beta' => false,
    //     'mock' => false,
    // ],
    // 'twitter' => [
    //     'developers' => 'https://developer.twitter.com/',
    //     'icon' => 'icon-twitter',
    //     'enabled' => false,
    //     'beta' => false,
    //     'mock' => false,
    // ],
    // Keep Last
    'mock' => [
        'developers' => 'https://appwrite.io',
        'icon' => 'icon-appwrite',
        'enabled' => true,
        'form' => false,
        'beta' => false,
        'mock' => true,
    ]
];
