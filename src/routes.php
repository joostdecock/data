<?php

// Anonymous routes
$app->post('/signup', 'UserController:signup');
$app->post('/resend', 'UserController:resend');
$app->get('/activate/{handle}/{token}', 'UserController:activate');
$app->post('/recover', 'UserController:recover');
$app->post('/reset', 'UserController:reset');

// Authenticated routes

// Preflight
$app->options('/[{path:.*}]', function($request, $response, $path = null) {
    $settings = require __DIR__ . '/../src/settings.php';
    return $response
        ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin'])
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// The 'am I logged in' check
$app->get('/auth', function ($request, $response) {
    $settings = require __DIR__ . '/../src/settings.php';
    $response->getBody()->write(json_encode(['result' => 'ok']));
    return $response
        ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin']);
});

// Login
$app->post('/login', 'UserController:login');


// Catch-all GET requests that don't match anything
$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
