<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Error;

class ErrorControllerTest extends \PHPUnit\Framework\TestCase
{
    private $errorData = [
        'level' => 64,
        'type' => 'php-error',
        'message' => 'Unknown error',
        'file' => 'ErrorControllerTest.php',
        'line' => 24,
        'origin' => 'data.freesewing.org',
        'user' => 'jrdoe',
        'ip' => '127.0.0.1',
        'status' => 'closed',
        'raw' => 'raw data'
    ];

    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testLog()
    {
        $data = $this->errorData;
        $data['message'] = time();

        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->id));
        
        for($i=0;$i<33;$i++) $this->logError();
        $response = $this->app->call('POST','/error', $this->errorData);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ignored');
        $this->assertEquals($json->reason, 'error_is_familiar');
    }

    private function logError()
    {
        $response = $this->app->call('POST','/error', $this->errorData);
    }
}
