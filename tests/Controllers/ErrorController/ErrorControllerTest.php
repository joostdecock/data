<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Error;
use Freesewing\Data\Objects\User;

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

    /** Helper to create an authenticated session */
    private function getSession($rand='', $admin=true)
    {
        $session = new \stdClass();

        $session->user = new User($this->app->getContainer());
        
        $email = $rand.time().debug_backtrace()[1]['function'].'.UserController@freesewing.org';
        $session->user->create($email, 'bananas');
        $session->user->setStatus('active');
        if($admin) $session->user->setRole('admin');
        else $session->user->setRole('user');
        $session->user->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];

        $response = $this->app->call('POST','/login', $data);
        $json = json_decode((string)$response->getBody());

        $session->token = $json->token;
        // Refresh user data
        $session->user->loadFromId($session->user->getId());

        return $session;
    }

    public function testLog()
    {
        $data = $this->errorData;
        $data['message'] = time();
        // Throw in a different parameter for good measure
        $data['break'] = "Please don't";

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

    public function testLogIncomplete()
    {
        $response = $this->app->call('POST','/error');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'missing_input');
    }

    public function testLoadRecentErrors()
    {
        // Make sure we have at least 1 error
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        
        $response = $this->app->call('GET','/errors');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(is_int($json->count));
        $this->assertTrue(isset($json->errors));
    }

    public function testLoadRecentErrorsNoErrors()
    {
        $db = $this->app->getContainer()->get('db');
        $sql = 'TRUNCATE errors';
        $db->query($sql);
        
        $response = $this->app->call('GET','/errors');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->count, 0);
    }

    public function testLoadAllErrors()
    {
        // Make sure we have at least 1 error
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        
        $response = $this->app->call('GET','/errors/all');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(is_int($json->count));
        $this->assertTrue(isset($json->errors));
    }

    public function testLoadAllErrorsNoErrors()
    {
        $db = $this->app->getContainer()->get('db');
        $sql = 'TRUNCATE errors';
        $db->query($sql);
        
        $response = $this->app->call('GET','/errors/all');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->count, 0);
    }

    public function testLoadGroup()
    {
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        $response = $this->app->call('GET','/errors/'.$hash);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->group->hash, $hash);
    }

    public function testLoadOpenGroup()
    {
        // Create error
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        // Update status to open
        $session = $this->getSession();
        $response = $this->app->call('POST','/admin/errors/'.$hash, ['status' => 'open'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        // Verify group status
        $response = $this->app->call('GET','/errors/'.$hash);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->group->hash, $hash);
        $this->assertEquals($json->group->status, 'open');
    }

    public function testLoadMutedGroup()
    {
        // Create error
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        // Update status to muted
        $session = $this->getSession();
        $response = $this->app->call('POST','/admin/errors/'.$hash, ['status' => 'muted'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        // Verify group status
        $response = $this->app->call('GET','/errors/'.$hash);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->group->hash, $hash);
        $this->assertEquals($json->group->status, 'muted');
    }

    public function testLoadClosedGroup()
    {
        // Create error
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        // Update status to closed
        $session = $this->getSession();
        $response = $this->app->call('POST','/admin/errors/'.$hash, ['status' => 'closed'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        // Verify group status
        $response = $this->app->call('GET','/errors/'.$hash);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->group->hash, $hash);
        $this->assertEquals($json->group->status, 'closed');
    }

    public function testLoadUnknownGroup()
    {
        $response = $this->app->call('GET','/errors/unknown');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'failed_to_load_group');
    }

    public function testUpdateGroup()
    {
        $session = $this->getSession();
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        $response = $this->app->call('POST','/admin/errors/'.$hash, ['status' => 'open'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
    }

    public function testUpdateUnknownGroup()
    {
        $session = $this->getSession();

        $response = $this->app->call('POST','/admin/errors/unknown', ['status' => 'open'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'no_such_group');
    }

    public function testUpdateGroupNoAdmin()
    {
        $session = $this->getSession('', false);
        $data = $this->errorData;
        $data['message'] = time();
        $response = $this->app->call('POST','/error', $data);
        $json = json_decode((string)$response->getBody());
        $hash = $json->hash;

        $response = $this->app->call('POST','/admin/errors/'.$hash, ['status' => 'open'], $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
        $this->assertEquals($json->role, 'user');
        $this->assertEquals($json->id, $session->user->getId());
    }

    private function logError()
    {
        $response = $this->app->call('POST','/error', $this->errorData);
    }
}
