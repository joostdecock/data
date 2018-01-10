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
        //FIXME: I can't seem to suppress this output
        //$response = $this->app->call('GET','/');

        //$this->assertEquals($response->getStatusCode(), 401);
    }
}
