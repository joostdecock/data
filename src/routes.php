<?php

/*******************/
/* Prefetch routes */
/*******************/

// YAML info bundle
$app->get('/info/yaml', 'InfoController:asYaml');

// JSON info bundle
$app->get('/info/json', 'InfoController:asJson');


/********************/
/* Anonymous routes */
/********************/

// Preflight requests 
$app->options('/[{path:.*}]', function($request, $response, $path = null) {
    $settings = require __DIR__ . '/../src/settings.php';
    return $response
        ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin'])
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Signup user
$app->post('/signup', 'UserController:signup');

// Resend user activation email
$app->post('/resend', 'UserController:resend');

// Activate user account
$app->get('/activate/{handle}/{token}', 'UserController:activate');

// Confirm user email change
$app->get('/confirm/{handle}/{token}', 'UserController:confirm');

// Recover user password
$app->post('/recover', 'UserController:recover');

// Reset user password
$app->post('/reset', 'UserController:reset');

// Download draft is anonymous coz no ajax
$app->get('/download/{handle}/{format}', 'DraftController:download');

// Referral logging
$app->post('/referral', 'ReferralController:log');

// Load shared draft
$app->get('/shared/draft/{handle}', 'DraftController:loadShared');

/************************/
/* Authenticated routes */
/************************/

// Check for authenticated user
$app->get('/auth', function ($request, $response) {
    $settings = require __DIR__ . '/../src/settings.php';
    $response->getBody()->write(json_encode(['result' => 'ok']));
    return $response
        ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin']);
});

// User login
$app->post('/login', 'UserController:login');

// Load user account data
$app->get('/account', 'UserController:load');

// Update user account
$app->put('/account', 'UserController:update');

// Delete user account
$app->delete('/account', 'UserController:remove');

// Load model data
$app->get('/model/{handle}', 'ModelController:load');

// Update model
$app->put('/model/{handle}', 'ModelController:update');

// Create model
$app->post('/model', 'ModelController:create');

// Remove model
$app->delete('/model/{handle}', 'ModelController:remove');

// Create draft
$app->post('/draft', 'DraftController:create');

// Recreate draft
$app->post('/redraft', 'DraftController:recreate');

// Load draft data
$app->get('/draft/{handle}', 'DraftController:load');

// Update draft
$app->put('/draft/{handle}', 'DraftController:update');

// Remove draft
$app->delete('/draft/{handle}', 'DraftController:remove');


/*******************/
/* Catch-all route */
/*******************/


// Catch-all GET requests that don't match anything
$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
