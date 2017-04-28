<?php
// Routes

$app->post('/signup', 'UserController:signup');
$app->get('/activate/{handle}/{token}', 'UserController:activate');





$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
