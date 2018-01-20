<?php

namespace Freesewing\Data\Tests;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\Stubs\Mailgun;
use Freesewing\Data\Tests\Stubs\SwiftMailer;

class TestApp extends App 
{
    const CORE_API = 'https://core.freesewing.org';

    public function __construct()
    {
        // Overwrite settings for testing
        $settings = require __DIR__ . '/../src/settings.php';
        $settings['settings']['db']['type'] = $settings['settings']['testdb']['type'];
        $settings['settings']['db']['database'] = $settings['settings']['testdb']['database'];
        $settings['settings']['storage'] = $settings['settings']['teststorage'];
        $settings['settings']['logger'] = $settings['settings']['testlogger'];
        $settings['settings']['displayErrorDetails'] = true;
        $settings['settings']['jwt']['forceEncryption'] = false;
        $settings['settings']['jwt']['secret'] = 'test'; 
        $settings['settings']['app']['core_api'] = self::CORE_API;

        // Run the Slim\App contructor
        parent::__construct($settings);

        // We need to have the $app var be our Slim\App object to load these
        $app = $this;

        // Load dependencies
        require __DIR__ . '/../src/dependencies.php';
        // Load middleware, and inject logger for debugging unit tests
        $settings['settings']['jwt']['logger'] = $container['logger'];
        require __DIR__ . '/../src/middleware.php';
        // Load routes
        require __DIR__ . '/../src/routes.php';

        // Replace external dependencies with stubs
        $container['Mailgun'] = function ($c) { return new Mailgun(); };
        $container['SwiftMailer'] = function ($c) { return new SwiftMailer(); };
    }

    public function call($method, $route, $data=null, $token=null)
    {
        // Mock the environment
        if($token === null) {
            $environment = Environment::mock([
                 'REQUEST_METHOD' => $method,
                 'REQUEST_URI' => $route
             ]);
        } else {
            $environment = Environment::mock([
                 'REQUEST_METHOD' => $method,
                 'REQUEST_URI' => $route,
                 'HTTP_AUTHORIZATION' => 'Bearer '.$token
             ]);
        }

        // Create request, add data if needed
        $request = Request::createFromEnvironment($environment);
        if (isset($data)) $request = $request->withParsedBody($data);
        
        return $this->process($request, new Response());
    }

    public function mockMailgun()
    {
        // Create a Mailgun stub
        $stub = $this->createMock(Mailgun::class);
        
        // Mock the create method
        $stub->method('create')
            ->willReturn(new Mailgun());
    }
}
