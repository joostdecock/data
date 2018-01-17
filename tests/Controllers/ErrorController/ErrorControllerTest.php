<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Error;

class ErrorControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testLog()
    {
        $data = [
            'level' => 8,
            'type' => 'php-error',
            'message' => 'Unit test fake error message',
            'file' => 'ErrorControllerTest.php',
            'line' => 24,
            'origin' => 'joost.data.freesewing.org',
        ];

        $response = $this->app->call('POST','/error', $data);
    //var_dump($response);    
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->id));
    }
}
