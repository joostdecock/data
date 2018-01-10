<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;

class UserControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testSignup()
    {
        $environment = Environment::mock([
             'REQUEST_METHOD' => 'GET',
             'REQUEST_URI' => '/'
        ]);

		// Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);
        
		// Add request data, if it exists
        if (isset($requestData)) {
            $request = $request->withParsedBody(['foo' => 'bar']);
        }

        // Set up a response object
        $response = new Response();

        $app = new TestApp();
        
		// Process the application
        $response = $app->process($request, $response);
        
        // Return the response
        echo "$response";
    }
}
