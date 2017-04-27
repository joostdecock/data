<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => dirname(__DIR__) . '/templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => dirname(__DIR__) . '/logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database
        'db' => [
            'host' => getenv('FREESEWING_DATA_DB_HOST', true),
            'database' => getenv('FREESEWING_DATA_DB_DATABASE', true),
            'user' => getenv('FREESEWING_DATA_DB_USER', true),
            'password' => getenv('FREESEWING_DATA_DB_PASSWORD', true),
        ],
        
        // Storage settings
        'storage' => [
            'static_path' => dirname(__DIR__) . '/public/static',
        ],

        // App settings
        'app' => [
            'user_status' => ['active', 'inactive', 'blocked'],
            'user_role' => ['user', 'moderator', 'admin'],
            'handle_type' => ['user', 'model', 'draft'],
        ],

        // Migration settings
        'mmp' => [
            'public_path' => 'https://makemypattern.com/sites/default/files/styles/user_picture/public',
        ],
    ],
];
