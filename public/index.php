<?php
require __DIR__ . '/../vendor/autoload.php';          // Load autoloader
$settings = require __DIR__ . '/../src/settings.php'; // Load settings

// Use bail for error handling?
use Freesewing\Bail\ErrorHandler;
if($settings['settings']['bail']['bail_enabled'] === true) {
    ErrorHandler::init(
        $settings['settings']['bail']['api'],
        $settings['settings']['bail']['origin'],
        __DIR__.'/../templates/error.html'
    );
}
    
// Instantiate the app
$app = new \Slim\App($settings);
require __DIR__ . '/../src/dependencies.php'; // Load dependencies
require __DIR__ . '/../src/middleware.php';  // Load middleware
require __DIR__ . '/../src/routes.php';      // Load routes

// Run app
$app->run();
