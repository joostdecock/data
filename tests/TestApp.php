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
    public function __construct()
    {
        // Overwrite settings for testing
        $settings = require __DIR__ . '/../src/settings.php';
        $settings['settings']['db'] = $settings['settings']['testdb'];
        $settings['settings']['storage'] = $settings['settings']['teststorage'];
        $settings['settings']['logger'] = $settings['settings']['testlogger'];
        $settings['settings']['displayErrorDetails'] = true;
        $settings['settings']['forceEncryption'] = false;

        // Run the Slim\App contructor
        parent::__construct($settings);

        // We need to have the $app var be our Slim\App object to load these
        $app = $this;
        require __DIR__ . '/../src/dependencies.php';
        require __DIR__ . '/../src/middleware.php';
        require __DIR__ . '/../src/routes.php';

        // Replace external dependencies with stubs
        $container['Mailgun'] = function ($c) { return new Mailgun(); };
        $container['SwiftMailer'] = function ($c) { return new SwiftMailer(); };
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
