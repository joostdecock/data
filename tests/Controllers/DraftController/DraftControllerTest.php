<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Draft;

class DraftControllerTest extends \PHPUnit\Framework\TestCase
{
    private $draftData = [
            'pattern' => 'TrayvonTie',
            'seamAllowance' => 'metric',
			'customSa' => 1,
			'scope' => 'all',
			'theme' => 'Basic',
			'lang' => 'en',
			'tipWidth' => 6,
			'knotWidth' =>  3.5,
			'sa' => 1,
			'userUnits' => 'metric',
			'defaultMetricSa' => 1,
        ];

    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** Helper to create an authenticated session */
    private function getSession($rand='')
    {
        $session = new \stdClass();

        $session->user = new User($this->app->getContainer());
        
        $email = $rand.time().debug_backtrace()[1]['function'].'.UserController@freesewing.org';
        $session->user->create($email, 'bananas');
        $session->user->setStatus('active');
        $session->user->save();
        $data = [
            'email' => $email,
            'password' => 'bananas',
        ];

        $response = $this->app->call('POST','/login', $data);
        $json = json_decode((string)$response->getBody());

        $session->token = $json->token;
        // Refresh user data
        $session->user->loadFromId($session->user->getId());

        return $session;
    }

    public function testCreateRecreateFork()
    {
        $session = $this->getSession();
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $data['draft'] = $json->handle;
        $response = $this->app->call('POST','/redraft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));
        
        $data['fork'] = 'true';
        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));
    }

    public function testCreateOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session1->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'model_not_yours');
    }

    public function testRecreateOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session1->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $data['draft'] = $json->handle;
        $response = $this->app->call('POST','/redraft', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'draft_not_yours');
    }

    public function testLoad()
    {
        $session = $this->getSession();
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $response = $this->app->call('GET','/draft/'.$handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        //var_dump($json);
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->draft->handle, $handle);
        $this->assertEquals($json->draft->userHandle, $session->user->getHandle());
        $this->assertEquals($json->draft->model->handle, $model->getHandle());
        $this->assertTrue(isset($json->draft->svg));
        $this->assertTrue(isset($json->draft->compared));
    }

    public function testLoadUnsharedDraft()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session1->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $response = $this->app->call('GET','/draft/'.$handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        //var_dump($json);
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'draft_not_yours_and_not_shared');
    }

    public function testUpdate()
    {
        $session = $this->getSession();
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $data = [
            'name' => 'This name was updated',
            'notes' => 'These are the updated notes',
            'shared' => true,
        ];

        $response = $this->app->call('PUT','/draft/'.$handle, $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->handle, $handle);
        $this->assertEquals($json->name, 'This name was updated');
        $this->assertEquals($json->notes, 'These are the updated notes');
        $this->assertEquals($json->shared, 1);
    }

    public function testUpdateOtherDraft()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session1->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $data = [
            'name' => 'This name was updated',
            'notes' => 'These are the updated notes',
            'shared' => true,
        ];

        $response = $this->app->call('PUT','/draft/'.$handle, $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'draft_not_yours');
    }

    public function testDownload()
    {
        $session = $this->getSession();
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $response = $this->app->call('GET',"/download/$handle/svg");
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($response->getHeader('Content-Type')[0], 'image/svg+xml');
        
        $response = $this->app->call('GET',"/download/$handle/pdf");
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($response->getHeader('Content-Type')[0], 'application/pdf');
    }

    public function testRemove()
    {
        $session = $this->getSession();
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $response = $this->app->call('DELETE','/draft/'.$handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'draft_removed');
        $draft = new Draft($this->app->getContainer());
        $this->assertFalse($draft->loadFromHandle($handle));
    }

    public function testRemoveOtherDraft()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        // We need a model
        $model = new Model($this->app->getContainer());
        $model->create($session1->user);
        $model->setMeasurement('centerbackneckToWaist', 48);
        $model->setMeasurement('neckCircumference', 42);
        $model->save();

        $data = $this->draftData;
        $data['model'] =  $model->getHandle();

        $response = $this->app->call('POST','/draft', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(isset($json->handle));

        $response = $this->app->call('DELETE','/draft/'.$handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'not_your_draft');
    }

}
