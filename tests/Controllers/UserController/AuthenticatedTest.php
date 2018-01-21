<?php

namespace Freesewing\Data\Tests\Controllers\UserController;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Mailgun\Mailgun;
use Mailgun\Api\Message;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Tools\AvatarKit;

class AuthenticatedTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** Helper to create an authenticated session */
    private function getSession($rand='')
    {
        $session = new \stdClass();

        $session->user = clone $this->app->getContainer()->get('User');
        
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

    public function testAuth()
    {
        $session = $this->getSession();

        $response = $this->app->call('GET','/auth', null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->id, $session->user->getId());
        $this->assertEquals($json->email, $session->user->getEmail());
    }

    public function testLoad()
    {
        $session = $this->getSession();

        $response = $this->app->call('GET','/account', null, $session->token);
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->account->id, $session->user->getId());
        $this->assertEquals($json->account->email, $session->user->getEmail());
        $this->assertEquals($json->account->handle, $session->user->getHandle());
        $this->assertEquals($json->account->status, $session->user->getStatus());
        $this->assertEquals($json->account->created, $session->user->getCreated());
        $this->assertEquals($json->account->login, $session->user->getLogin());
        $this->assertEquals($json->account->picture, $session->user->getPicture());
        $this->assertEquals($json->account->data, json_decode(json_encode($session->user->getData())));
    }

    public function testUpdate()
    {
        $session = $this->getSession();
        $username = time().' Test user';
        $data = [
            'username' => $username,
            'address' => 'Something street',
            'birthmonth' => '12',
            'birthday' => '10',
            'twitter' => 'j__st',
            'instagram' => 'freesewing_org',
            'github' => 'joostdecock',
            'picture' => '',
            'units' => 'imperial',
            'theme' => 'paperless',
            'picture' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAgAAZABkAAD/7AARRHVja3kAAQAEAAAAZAAA/+4ADkFkb2JlAGTAAAAAAf/bAIQAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQICAgICAgICAgICAwMDAwMDAwMDAwEBAQEBAQECAQECAgIBAgIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMD/8AAEQgAFwAXAwERAAIRAQMRAf/EAHkAAAMBAQAAAAAAAAAAAAAAAAYJCgQLAQEAAgMAAAAAAAAAAAAAAAAHBAYCAwUQAAEFAQEAAwEBAAAAAAAAAAUCAwQGBwEIEhMJERURAAIBAgQEBQMFAQAAAAAAAAECAxEEIRITBQAxQQZRcSIUB2GRMoFCciMWFf/aAAwDAQACEQMRAD8A33X0fcNooHorRthN7xaPTFn9g6vWwFcCJ9DQJVZyGrXwvSc7oWX1ijtQ68JqcKAOit8Zht9JEpzqpUjrz0hbvaJ2pedxr3v8g/8AU1Ye2rz4ta12tpAhgl3g7vG8lvaFq0v2tI0eqZZFjDZXAMgKX8kbdsH+o+B/8SySWdlNudz3U0JXJBLK95HaHdQxBymEQaEbhogpSRVBcsRZ3QmJAJgLAvJKwGVlideXU6r6ZvNm0Hp+vROTbHUxwQBoM5q03UFB4p2WECzSZtrnOpTFW6laE0247Q+RrfbB3DLa3g2eViqSZ61YcwVDk4fxpzAxBom2vdvxlc7+dji3HbWv0AZo9JVGWvMMYwKHkDUDljiDwr/y3sP6j4fe9DdKG/cbEPbKR6gyQ9SLxW96JZ7YanasX1VedlA8A2Ed/wAjRa2cgDpYCYOdYJpVxMXnVfe624lO+2rtccVgs/vDG+t6ZBRNECvrxaQyVGVMQRVajNwYqNxfe5Tu72gsVkQ25zWpjM/uVKiMQkssAgDGV58sfJScxUCs71vn71t/Kr1poeP1KtQ/WcPL9JD53sMUSPh6sMZdvh+pHogG9dQ2UBlZeeS5waIQQ8y/DZlJ5x5tCOKRB2m8WORkkA0/cGvlqHGvPEYHnTpxyd1tGuJxp/mYBQdCdIZajz5da04g/wAYtOlSM+r+O2igXKDmkSw2aSIS5NmwbBSLq6zAcpBSjQI8mCeomgUW1jGVuE+tttjB6H3ZPxa4p1atPPIm2r7qF/ZO5OqcV0yAUWM8vTTBQSBUjKDiRsWW0azwbete/tQZSAwcdGLilMhX8mJoy060HHQx9ObTsD3hjzO69eDsTaXqJnpaZbW5s1os5o0DKbgRFXGQlPOurKSykOKU4vrfXFOrSvqeK78eFku6LLexR1OkrE88cFbHzp9v14XrOxKRXDEKJTHQYYVLKKfxLHl4CnAlR71oWNidPyq8ZOf0rPgVk1FQrW8rm0jQsmvuZGbZZi8czLJRLYntcIjhJFcE8KKMMKhTokjiVPxuoeVXLlL61vJUt1E0BdyGR0OBY1BGYEEHAgg414sYFneQRT6hhuNNAUdXDAqoFR6SGDUzKfA+OHCjheefnGA2KNpnCM82zGMslg+bkb9mk2sNGFvx3YbToaXqy2CUZt9bP0wn0vRf59aeNfHiOckvPv5tcmjN7boarT645qeZ+/GbR2eo39sGthWivm+n7K/fry4Zlbrps2t69mHHcptmX03MbGT1KwWTVCdHqZm5GKjRbjJpWSZJUJVzesWhaZotkfjx4seOw3FixW33XX+O8ZZc59pb3kszTz6cYSGQqpkjLu2RsoADUA8enOppxqk9nb25iR2leR1WqrJlQF1zO7ZRTLTz+mPH/9k=',
        ];

        $response = $this->app->call('PUT','/account', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $jsdata = json_decode($json->data);
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'account/updated');
        $this->assertEquals($jsdata->account->units, $data['units']);
        $this->assertEquals($jsdata->account->theme, $data['theme']);
        $this->assertEquals($jsdata->patron->address, $data['address']);
        $this->assertEquals($jsdata->social->twitter, $data['twitter']);
        $this->assertEquals($jsdata->social->instagram, $data['instagram']);
        $this->assertEquals($jsdata->social->github, $data['github']);

        // Update user
        $session->user->loadFromId($session->user->getId());
        $this->assertEquals($session->user->getPicture(), $session->user->getHandle().'.jpg');
    }
    
    public function testUpdateEmailTaken()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'email' => $session1->user->getEmail(),
            'username' => md5($session1->user->getEmail()),
        ];

        $response = $this->app->call('PUT','/account', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'email_taken');
        $this->assertEquals($json->message, 'account/email-taken');
    }
    
    public function testUpdateUsernameTaken()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $data = [
            'username' => $session1->user->getUsername(),
        ];

        $response = $this->app->call('PUT','/account', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'username_taken');
        $this->assertEquals($json->message, 'account/username-taken');
    }
    
    public function testUpdateChangeEmail()
    {
        $session = $this->getSession();
        $newEmail = 'new'.$session->user->getEmail();
        $data = [
            'email' => $newEmail,
            'username' => md5($session->user->getEmail()),
        ];

        $response = $this->app->call('PUT','/account', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'account/updated');
        $this->assertEquals($json->pendingEmail, $newEmail);
    }
    
    public function testLoadPofile()
    {
        $session = $this->getSession();
        $response = $this->app->call('GET','/profile/'.$session->user->getHandle(), null, $session->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->profile->username, $session->user->getUsername());
        $this->assertEquals($json->profile->handle, $session->user->getHandle());
        $this->assertEquals($json->profile->created, $session->user->getCreated());
        $this->assertEquals($json->profile->status, $session->user->getStatus());
        $handle = $session->user->getHandle();
        $this->assertEquals($json->profile->pictureSrc, '/static/users/'.substr($handle,0,1)."/$handle/account/$handle.svg");
    }
    
    public function testRemove()
    {
        $session = $this->getSession();
        $response = $this->app->call('DELETE','/account', null, $session->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->reason, 'user_removed');
        $this->assertFalse($session->user->loadFromEmail($session->user->getEmail()));
    }
    
    public function testExport()
    {
        $session = $this->getSession();
        $response = $this->app->call('GET','/export', null, $session->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$json->archive);
        $this->assertTrue(file_exists($path));
    }
    
    public function testRole()
    {
        $session = $this->getSession();
        $response = $this->app->call('GET','/role', null, $session->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->role, $session->user->getRole());
    }
}
