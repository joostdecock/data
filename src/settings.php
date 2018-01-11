<?php
include('referrals.php');
return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'forceEncryption' => true, // Don't allow to access this over an unencrypted connection

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
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_DB'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
        ],
        'testdb' => [
            'host' => getenv('TEST_DB_HOST'),
            'database' => getenv('TEST_DB_DB'),
            'user' => getenv('TEST_DB_USER'),
            'password' => getenv('TEST_DB_PASS'),
        ],
        
        // Mailgun
        'mailgun' => [
            'api_key' => getenv('MAILGUN_KEY'),
            'template_path' => dirname(__DIR__) . '/templates/email',
            'instance' => getenv('MAILGUN_INSTANCE'),
        ],
        
        // SEPs (shitty email providers - basically Microsoft domains) will not deliver
        // MailGun messages, so we send email through GMAIL for these domains
        // using SwiftMailer
        'swiftmailer' => [
            'domains' => [
                'btinternet.com',
                'hotmail.be',
                'hotmail.de',
                'hotmail.fr',
                'hotmail.com',
                'hotmail.co.uk',
                'live.ca',
                'live.com',
                'live.co.uk',
                'live.com.au',
                'live.nl',
                'msn.com',
                'outlook.com',
                'snkmail.com',
                'yahoo.com',
                'yahoo.co.uk',
                'yahoo.co.nz',
                'yahoo.de',
                'yahoo.fr',
                'ymail.com',
            ],
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' =>  getenv('GMAIL_USER'),
            'password' =>  getenv('GMAIL_SECRET'),
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
            'jwt_secret' => getenv('JWT_SECRET'),
            'jwt_lifetime' => "1 month",
            'origin' => getenv('ORIGIN'),
            'user_status' => ['active', 'inactive', 'blocked'],
            'user_role' => ['user', 'moderator', 'admin'],
            'handle_type' => ['user', 'model', 'draft'],
            'static_path' => '/static',
            'female_measurements' => ['underBust'],
            'motd' => '
**Tip**: These are your notes.
You can write whatever you want here.',  
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
        
        // Measurement titles
        'measurements' => [
            'acrossBack' => 'Across back',
            'bicepsCircumference' => 'Biceps circumference',
            'centerBackNeckToWaist' => 'Centerback neck to waist',
            'chestCircumference' => 'Chest circumference',
            'headCircumference' => 'Head circumference',
            'hipsCircumference' => 'Hips circumference',
            'hipsToUpperLeg' => 'Hips to upper leg',
            'inseam' => 'Inseam',
            'naturalWaist' => 'Natural waist',
            'naturalWaistToFloor' => 'Natural waist to floor',
            'naturalWaistToHip' => 'Natural waist to hip',
            'naturalWaistToSeat' => 'Natural waist to seat',
            'naturalWaistToUnderbust' => 'Natural waist to underbust',
            'neckCircumference' => 'Neck circumference',
            'seatCircumference' => 'Seat circumference',
            'seatDepth' => 'Seat depth',
            'shoulderSlope' => 'Shoulder slope',
            'shoulderToElbow' => 'Shoulder to elbow',
            'shoulderToShoulder' => 'Shoulder to shoulder',
            'shoulderToWrist' => 'Shoulder to wrist',
            'underBust' => 'Underbust',
            'upperLegCircumference' => 'Upper leg circumference',
            'wristCircumference' => 'Wrist circumference',
        ],

        // Referral groups
        'referrals' => getReferralGroups(),
    ],
];
