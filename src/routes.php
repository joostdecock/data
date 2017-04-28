<?php
// Routes

$app->post('/user/signup', 'UserController:signup');
$app->get('/user/activate/{handle}/{token}', 'UserController:activate');





$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
