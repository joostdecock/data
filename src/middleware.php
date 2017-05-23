<?php
// Application middleware

$jwtOptions = [
    "secure" => false,
    'path' => '/',
    'passthrough' => ['/signup', '/login', '/recover', '/reset', '/activate', '/resend','/confirm','/patterns','/measurements','/patternmap','/patternpam','/download/'],
    'attribute' => 'jwt',
    'secret' => getenv("JWT_SECRET"),
    "error" => function ($request, $response, $arguments) {
        $settings = require __DIR__ . '/../src/settings.php';
        echo file_get_contents(dirname(__DIR__).'/templates/index.html');
    }
];

$jwt = new \Slim\Middleware\JwtAuthentication($jwtOptions);
$app->add($jwt);
