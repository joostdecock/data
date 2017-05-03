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
            'path' => getenv('LOG'),
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database
        'db' => [
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_DB'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
        ],
        
        // Mailgun
        'mailgun' => [
            'api_key' => getenv('MAILGUN_KEY', true),
            'template_path' => dirname(__DIR__) . '/templates/email',
        ],

        // Storage settings
        'storage' => [
            'static_path' => dirname(__DIR__) . '/public/static',
        ],

        // App settings
        'app' => [
            'data_api' => getenv('DATA_API'),
            'site' => getenv('SITE'),
            'jwt_secret' => getenv('JWT_SECRET'),
            'origin' => getenv('ORIGIN'),
            'user_status' => ['active', 'inactive', 'blocked'],
            'user_role' => ['user', 'moderator', 'admin'],
            'handle_type' => ['user', 'model', 'draft'],
            'static_path' => '/static',
        ],

        // Migration settings
        'mmp' => [
            'public_path' => 'https://makemypattern.com/sites/default/files/styles/user_picture/public',
        ],
    ],
];
