<?php
// Application middleware

$app->add(new \Slim\Middleware\JwtAuthentication([
    "secure" => false,
    'path' => '/',
    'passthrough' => ['/signup', '/reset', '/activate'],
    'attribute' => 'jwt',
    'secret' => getenv("FREESEWING_JWT_SECRET")
]));
