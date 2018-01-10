<?php

namespace Freesewing\Data\Tests;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

class TestApp extends \Slim\App 
{
    public function __construct()
    {

        // Instantiate the app
        $settings = require __DIR__ . '/../src/settings.php';

        // Overwrite settings for testing
        $settings['settings']['storage'] = $settings['settings']['teststorage'];
        $settings['settings']['logger'] = $settings['settings']['testlogger'];
        $settings['settings']['displayErrorDetails'] = true;
        $settings['settings']['forceEncryption'] = false;

        // We need to have the $app var be our Slim\App object to load these
        $app = $this;
        parent::__construct($settings);
        require __DIR__ . '/../src/dependencies.php';
        require __DIR__ . '/../src/middleware.php';
        require __DIR__ . '/../src/routes.php';
    }

    public function call($method, $route, $data=null)
    {
        // Mock the environment
        $environment = Environment::mock([
             'REQUEST_METHOD' => $method,
             'REQUEST_URI' => $route
        ]);

        // Create request, add data if needed
        $request = Request::createFromEnvironment($environment);
        if (isset($data)) $request = $request->withParsedBody($data);

        $response = $this->process($request, new Response());

        return $response;
    }
}
