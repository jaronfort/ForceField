<?php
return [
    'session' => [
        'database' => [
            'enabled' => false
        ],
        'domain' => '*'
    ],
    'ssl' => [
        'enabled' => false,
        'force' => false
    ],
    'encryption' => [
        'cypher' => 'AES-256-CBC',
        'key' => 'vnuqp598q34hiuawebrpq93urhadf09j30$1@!045'
    ],
    'password' => [
        'cypher' => PASSWORD_BCRYPT,
        'cost' => 10,
        'salt' => '' // Add some password salt
    ],
    'obfuscate' => [
        'enabled' => ff_mode() != 'development'
    ],
    'allowedOrigins' => true,
];