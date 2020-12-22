<?php

return [
    [
        'name' => '_APP_ENV',
        'default' => 'production',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_OPTIONS_ABUSE',
        'default' => 'enabled',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_OPTIONS_FORCE_HTTPS', 
        'default' => 'enabled',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_OPENSSL_KEY_V1',
        'default' => 'your-secret-key',
        'required' => true,
        'question' => 'Choose a secret API key, make sure to make a backup of your key in a secure location',
    ],
    [
        'name' => '_APP_DOMAIN',
        'default' => 'localhost',
        'required' => true,
        'question' => 'Enter your Appwrite hostname',
    ],
    [
        'name' => '_APP_DOMAIN_TARGET',
        'default' => 'localhost',
        'required' => true,
        'question' => "Enter a DNS A record hostname to serve as a CNAME for your custom domains.\nYou can use the same value as used for the Appwrite hostname.",
    ],
    [
        'name' => '_APP_REDIS_HOST',
        'default' => 'redis',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_REDIS_PORT',
        'default' => '6379',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_DB_HOST',
        'default' => 'mariadb',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_DB_PORT',
        'default' => '3306',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_DB_SCHEMA',
        'default' => 'appwrite',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_DB_USER',
        'default' => 'user',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_DB_PASS',
        'default' => 'password',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_INFLUXDB_HOST',
        'default' => 'influxdb',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_INFLUXDB_PORT',
        'default' => '8086',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_STATSD_HOST',
        'default' => 'telegraf',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_STATSD_PORT',
        'default' => '8125',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_SMTP_HOST',
        'default' => 'smtp',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_SMTP_PORT',
        'default' => '25',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_SMTP_SECURE',
        'default' => '',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_SMTP_USERNAME',
        'default' => '',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_SMTP_PASSWORD',
        'default' => '',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_STORAGE_LIMIT',
        'default' => '10000000',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_FUNCTIONS_TIMEOUT',
        'default' => '900',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_FUNCTIONS_CONTAINERS',
        'default' => '10',
        'required' => false,
        'question' => '',
    ],
    [
        'name' => '_APP_MAINTENANCE_INTERVAL',
        'default' => '86400',
        'required' => false,
        'question' => '',
    ],
];