<?php
// Application middleware

$jwtOptions = [
    "secure" => false,
    'path' => '/',
    'passthrough' => ['/signup', '/login', '/recover', '/reset', '/activate', '/resend'],
    'attribute' => 'jwt',
    'secret' => getenv("JWT_SECRET"),
    "error" => function ($request, $response, $arguments) {
        $settings = require __DIR__ . '/../src/settings.php';
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
];

$jwt = new \Slim\Middleware\JwtAuthentication($jwtOptions);
$app->add($jwt);
