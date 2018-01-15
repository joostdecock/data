<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\User;

class ModelControllerTest extends \PHPUnit\Framework\TestCase
{
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

    public function testCreate()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'model/created');
        $this->assertEquals($json->handle, $model->getHandle());
        $this->assertEquals($json->units, $session->user->data->getNode('account.units'));
        $this->assertEquals($json->picture, $model->getHandle().'.svg');
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->pictureSrc);
        $this->assertTrue(file_exists($path));
    }

    public function testUpdate()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);

        $data = [
            'name' => 'Test model updated',
            'body' => 'female',
            'units' => 'imperial',
        ];

        $response = $this->app->call('PUT','/model/'.$json->handle, $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'model/updated');
        $this->assertEquals($json->units, 'imperial');
        $this->assertEquals($json->picture, $model->getHandle().'.svg');
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->pictureSrc);
        $this->assertTrue(file_exists($path));
    } 
    
    public function testUpdateMeasurement()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);

        $data = [
            'data' => '{"measurements":{"acrossBack":12}}',
        ];

        $response = $this->app->call('PUT','/model/'.$json->handle, $data, $session->token);
        $model->loadFromHandle($json->handle);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'model/updated');
        $this->assertEquals($json->units, 'metric');
        $this->assertEquals($model->getMeasurement('acrossBack'), 12);
    } 
    
    public function testUpdateMeasurementImperial()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);
        $model->setUnits('imperial');
        $model->save();

        $data = [
            'data' => '{"measurements":{"acrossBack": "12 1/4"}}',
        ];

        $response = $this->app->call('PUT','/model/'.$json->handle, $data, $session->token);
        $model->loadFromHandle($json->handle);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'model/updated');
        $this->assertEquals($json->units, 'imperial');
        $this->assertEquals($model->getMeasurement('acrossBack'), 12.25);
    } 
    
    public function testUpdatePicture()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);
        $model->setUnits('imperial');
        $model->save();

        $data = [
            'picture' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAgAAZABkAAD/7AARRHVja3kAAQAEAAAAZAAA/+4ADkFkb2JlAGTAAAAAAf/bAIQAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQICAgICAgICAgICAwMDAwMDAwMDAwEBAQEBAQECAQECAgIBAgIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMD/8AAEQgAFwAXAwERAAIRAQMRAf/EAHkAAAMBAQAAAAAAAAAAAAAAAAYJCgQLAQEAAgMAAAAAAAAAAAAAAAAHBAYCAwUQAAEFAQEAAwEBAAAAAAAAAAUCAwQGBwEIEhMJERURAAIBAgQEBQMFAQAAAAAAAAECAxEEIRITBQAxQQZRcSIUB2GRMoFCciMWFf/aAAwDAQACEQMRAD8A33X0fcNooHorRthN7xaPTFn9g6vWwFcCJ9DQJVZyGrXwvSc7oWX1ijtQ68JqcKAOit8Zht9JEpzqpUjrz0hbvaJ2pedxr3v8g/8AU1Ye2rz4ta12tpAhgl3g7vG8lvaFq0v2tI0eqZZFjDZXAMgKX8kbdsH+o+B/8SySWdlNudz3U0JXJBLK95HaHdQxBymEQaEbhogpSRVBcsRZ3QmJAJgLAvJKwGVlideXU6r6ZvNm0Hp+vROTbHUxwQBoM5q03UFB4p2WECzSZtrnOpTFW6laE0247Q+RrfbB3DLa3g2eViqSZ61YcwVDk4fxpzAxBom2vdvxlc7+dji3HbWv0AZo9JVGWvMMYwKHkDUDljiDwr/y3sP6j4fe9DdKG/cbEPbKR6gyQ9SLxW96JZ7YanasX1VedlA8A2Ed/wAjRa2cgDpYCYOdYJpVxMXnVfe624lO+2rtccVgs/vDG+t6ZBRNECvrxaQyVGVMQRVajNwYqNxfe5Tu72gsVkQ25zWpjM/uVKiMQkssAgDGV58sfJScxUCs71vn71t/Kr1poeP1KtQ/WcPL9JD53sMUSPh6sMZdvh+pHogG9dQ2UBlZeeS5waIQQ8y/DZlJ5x5tCOKRB2m8WORkkA0/cGvlqHGvPEYHnTpxyd1tGuJxp/mYBQdCdIZajz5da04g/wAYtOlSM+r+O2igXKDmkSw2aSIS5NmwbBSLq6zAcpBSjQI8mCeomgUW1jGVuE+tttjB6H3ZPxa4p1atPPIm2r7qF/ZO5OqcV0yAUWM8vTTBQSBUjKDiRsWW0azwbete/tQZSAwcdGLilMhX8mJoy060HHQx9ObTsD3hjzO69eDsTaXqJnpaZbW5s1os5o0DKbgRFXGQlPOurKSykOKU4vrfXFOrSvqeK78eFku6LLexR1OkrE88cFbHzp9v14XrOxKRXDEKJTHQYYVLKKfxLHl4CnAlR71oWNidPyq8ZOf0rPgVk1FQrW8rm0jQsmvuZGbZZi8czLJRLYntcIjhJFcE8KKMMKhTokjiVPxuoeVXLlL61vJUt1E0BdyGR0OBY1BGYEEHAgg414sYFneQRT6hhuNNAUdXDAqoFR6SGDUzKfA+OHCjheefnGA2KNpnCM82zGMslg+bkb9mk2sNGFvx3YbToaXqy2CUZt9bP0wn0vRf59aeNfHiOckvPv5tcmjN7boarT645qeZ+/GbR2eo39sGthWivm+n7K/fry4Zlbrps2t69mHHcptmX03MbGT1KwWTVCdHqZm5GKjRbjJpWSZJUJVzesWhaZotkfjx4seOw3FixW33XX+O8ZZc59pb3kszTz6cYSGQqpkjLu2RsoADUA8enOppxqk9nb25iR2leR1WqrJlQF1zO7ZRTLTz+mPH/9k=',
        ];

        $response = $this->app->call('PUT','/model/'.$json->handle, $data, $session->token);
        $model->loadFromHandle($json->handle);
        $json = json_decode((string)$response->getBody());
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->pictureSrc); 
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'model/updated');
        $this->assertEquals($json->units, 'metric');
        $this->assertEquals($json->picture, $model->getHandle().'.jpg');
        $this->assertTrue(file_exists($path));
    } 
    
    public function testUpdateOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2= $this->getSession(2);
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session1->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);

        $data = [
            'name' => 'Test model updated',
            'body' => 'female',
            'units' => 'imperial',
        ];

        $response = $this->app->call('PUT','/model/'.$json->handle, $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'model_not_yours');
    } 
    
    public function testLoad()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);

        $response = $this->app->call('GET','/model/'.$json->handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->model->pictureSrc); 
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->model->id, $model->getId());
        $this->assertEquals($json->model->user, $model->getUser());
        $this->assertEquals($json->model->name, 'Test model');
        $this->assertEquals($json->model->units, 'metric');
        $this->assertEquals($json->model->picture, $model->getHandle().'.svg');
        $this->assertTrue(file_exists($path));
    } 
    
    public function testLoadOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session1->token);
        $json = json_decode((string)$response->getBody());

        $response = $this->app->call('GET','/model/'.$json->handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'model_not_yours');
    } 
    
    public function testKlone()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $model = new Model($this->app->getContainer());
        $model->loadFromHandle($json->handle);
        $model->setNotes('These are the notes');
        $model->setMeasurement('acrossBack', 24);
        $model->save();

        $response = $this->app->call('POST','/clone/model/'.$json->handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->clone->data, json_decode(json_encode($model->getData())));
        $this->assertEquals($json->clone->units, $model->getUnits());
        $this->assertEquals($json->clone->notes, $model->getNotes());
    } 
    
    public function testKloneOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session1->token);
        $json = json_decode((string)$response->getBody());

        $response = $this->app->call('POST','/clone/model/'.$json->handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'model_not_yours');
    } 
    
    public function testRemove()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $handle = $json->handle;

        $response = $this->app->call('DELETE','/model/'.$json->handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'model_removed');
        $model = new Model($this->app->getContainer());
        $this->assertFalse($model->loadFromHandle($handle));
    } 
    
    public function testRemoveOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session1->token);
        $json = json_decode((string)$response->getBody());

        $response = $this->app->call('DELETE','/model/'.$json->handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'not_your_model');
    } 
    
    public function testExport()
    {
        $session = $this->getSession();
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session->token);
        $json = json_decode((string)$response->getBody());

        $response = $this->app->call('GET','/export/model/'.$json->handle, null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->formats->csv);
        $this->assertTrue(file_exists($path));
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->formats->json);
        $this->assertTrue(file_exists($path));
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->formats->yaml);
        $this->assertTrue(file_exists($path));
    } 
    
    public function testExportOtherModel()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'name' => 'Test model',
            'body' => 'male',
        ];

        $response = $this->app->call('POST','/model', $data, $session1->token);
        $json = json_decode((string)$response->getBody());

        $response = $this->app->call('GET','/export/model/'.$json->handle, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'model_not_yours');
    } 
}
