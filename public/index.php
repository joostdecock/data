<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

if(!defined('IS_TEST')) session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';

// Rollbar integration
use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;
if($settings['settings']['rollbar']['rollbar_enabled'] !== false) {
    Rollbar::init([
        'access_token' => $settings['settings']['rollbar']['access_token'],
        'environment' => $settings['settings']['rollbar']['environment'],
        'included_errno' => (E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_NOTICE)
    ]);
}

$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
