<?php
require_once('includes/__config.php');

return [
    'version' => '2.0.0-alpha1',
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'bail' =>
        [
            'bail_enabled' => false,
            'api' => getenv('BAIL_API'),
            'origin' => getenv('BAIL_ORIGIN'),
        ],
        'tile' => '/usr/local/bin/tile', // Location of the freesewing tile binary

        // Middleware settings
        'jwt' => [
            "secure" => true, // Don't allow access over an unencrypted connection
            'path' => '/',
            'passthrough' => [
                '/config/',
                '/taskrunner',
                '/migrate',
                '/signup', 
                '/newuser', 
                '/login', 
                '/recover', 
                '/reset', 
                '/activate', 
                '/resend',
                '/confirm',
                '/info/',
                '/shared/',
                '/download/',
                '/referral',
                '/comments/',
                '/status', 
                '/email/', 
                '/referrals/group', 
                '/debug', 
                '/patrons/list',
                '/error',
                '/errors',
                '/errors/all',
            ],
            'attribute' => 'jwt',
            'secret' => getenv("JWT_SECRET"),
            'lifetime' => "1 month",
            "error" => function ($request, $response, $arguments) {
                echo file_get_contents(dirname(__DIR__).'/templates/index.html');
            }
        ],
        
        // Renderer settings
        'renderer' => [
            'template_path' => dirname(__DIR__) . '/templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => getenv('LOG_FILE'),
            'level' => \Monolog\Logger::DEBUG,
        ],
        'testlogger' => [
            'name' => 'slim-app',
            'path' => '/tmp/data.freesewing.test.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database
        'db' => [
            'type' => 'mariadb',
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_DB'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
        ],
        'testdb' => [
            'type' => 'sqlite',
            'database' => __DIR__.'/../tests/sql/test.sq3',
        ],
        
        
        // SEPs (shitty email providers - basically Microsoft domains) will not deliver
        // MailGun messages, so we send email through GMAIL for these domains
        // using SwiftMailer
        'swiftmailer' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' =>  getenv('GMAIL_USER'),
            'password' =>  getenv('GMAIL_SECRET'),
            'from' => 'info@freesewing.org',
            'templates' => dirname(__DIR__) . '/templates/email',
        ],

        // Storage settings
        'storage' => [
            'static_path' => dirname(__DIR__) . '/public/static',
            'temp_path' => '/tmp',
        ],
        'teststorage' => [
            'static_path' => '/tmp',
            'temp_path' => '/tmp',
        ],

        // App settings
        'app' => [
            'data_api' => getenv('DATA_API'),
            'core_api' => getenv('CORE_API'),
            'site' => getenv('SITE'),
            'origin' => getenv('ORIGIN'),
            'user_status' => ['active', 'inactive', 'blocked'],
            'user_role' => ['user', 'moderator', 'admin'],
            'handle_type' => ['user', 'model', 'draft'],
            'static_path' => '/static',
            'female_measurements' => ['underBust'],
            'motd' => '
**Tip**: These are your notes.
You can write whatever you want here.',  
            'tasks' => 20,
        ],
        'i18n' => [
          'locales' => ['en', 'nl'],
          'translations'=> dirname(__DIR__) . '/locales',
        ],
        'badges' => [
            'login' => '2018',
        ],
        'patrons' => [
            'tiers' => [2,4,8],
        ],

        // Migration settings
        'mmp' => [
            'public_path' => 'https://makemypattern.com/sites/default/files/styles/user_picture/public',
        ],
        'forBreastsOnly' => [
            'underBust',
            'bustSpan',
            'highBust',
            'highPointShoulderToBust',
        ],
        "patternHandleToPatternClass" => __patternsToClassNames(), // Temporary needed until core v2
        "patternRequiredMeasurements" => __requiredMeasurements(),
        "measurements" => [
            'all' => __allMeasurements(),
            'breasts' => __breastsMeasurements(),
            'noBreasts' => __noBreastsMeasurements(),
        ]
    ],
];
