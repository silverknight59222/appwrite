<?php
/**
 * List of Appwrite Cloud Functions supported environments
 */
return [
    'node-14' => [
        'name' => 'Node.js',
        'version' => '14.5',
        'base' => 'node:14.5-alpine',
        'image' => 'appwrite/env-node-14.5:1.0.0',
        'build' => '/usr/src/code/docker/environments/node-14.5',
        'logo' => 'node.png',
    ],
    'php-7.4' => [
        'name' => 'PHP',
        'version' => '7.4',
        'base' => 'php:7.4-cli-alpine',
        'image' => 'appwrite/env-php-7.4:1.0.0',
        'build' => '/usr/src/code/docker/environments/php-7.4',
        'logo' => 'php.png',
    ],
    'php-8.0' => [
        'name' => 'PHP',
        'version' => '8.0',
        'base' => 'php:8.0-cli-alpine',
        'image' => 'appwrite/env-php-8.0:1.0.0',
        'build' => '/usr/src/code/docker/environments/php-8.0',
        'logo' => 'php.png',
    ],
    'ruby-2.7' => [
        'name' => 'Ruby',
        'version' => '2.7',
        'base' => 'ruby:2.7-alpine',
        'image' => 'appwrite/env-ruby-2.7:1.0.2',
        'build' => '/usr/src/code/docker/environments/ruby-2.7',
        'logo' => 'ruby.png',
    ],
    'ruby-3.0' => [
        'name' => 'Ruby',
        'version' => '3.0',
        'base' => 'ruby:3.0-alpine',
        'image' => 'appwrite/env-ruby-3.0:1.0.0',
        'build' => '/usr/src/code/docker/environments/ruby-3.0',
        'logo' => 'ruby.png',
    ],
    'python-3.8' => [
        'name' => 'Python',
        'version' => '3.8',
        'base' => 'python:3.8-alpine',
        'image' => 'appwrite/env-python-3.8:1.0.0',
        'build' => '/usr/src/code/docker/environments/python-3.8',
        'logo' => 'python.png',
    ],
    'deno-1.2' => [
        'name' => 'Deno',
        'version' => '1.2',
        'base' => 'hayd/deno:alpine-1.2.0',
        'image' => 'appwrite/env-deno-1.2:1.0.0',
        'build' => '/usr/src/code/docker/environments/deno-1.2',
        'logo' => 'deno.png',
    ],
    'deno-1.5' => [
        'name' => 'Deno',
        'version' => '1.5',
        'base' => 'hayd/deno:alpine-1.5.0',
        'image' => 'appwrite/env-deno-1.5:1.0.0',
        'build' => '/usr/src/code/docker/environments/deno-1.5',
        'logo' => 'deno.png',
    ],
    // 'dart-2.8' => [
    //     'name' => 'Dart',
    //     'version' => '2.8',
    //     'base' => 'google/dart:2.8',
    //     'image' => 'appwrite/env-dart:2.8',
    //     'build' => '/usr/src/code/docker/environments/dart-2.8',
    //     'logo' => 'dart.png',
    // ],
];