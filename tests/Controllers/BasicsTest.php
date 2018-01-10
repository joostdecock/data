<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;

class BasicsTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testSignup()
    {
        ob_start();
        $response = $this->app->call('GET','/');
        ob_end_clean();

        $this->assertEquals($response->getStatusCode(), 401);
//        $this->assertContains('request makes no sense', (string)$response);
    }
}
