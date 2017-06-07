<?php
// Application middleware

$jwtOptions = [
    "secure" => true,
    'path' => '/',
    'passthrough' => ['/signup', '/login', '/recover', '/reset', '/activate', '/resend','/confirm','/info/','/shared/','/download/','/referral'],
    'attribute' => 'jwt',
    'secret' => getenv("JWT_SECRET"),
    "error" => function ($request, $response, $arguments) {
        echo file_get_contents(dirname(__DIR__).'/templates/index.html');
    }
];

$jwt = new \Slim\Middleware\JwtAuthentication($jwtOptions);
$app->add($jwt);
