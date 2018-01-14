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

class AdminTest extends \PHPUnit\Framework\TestCase
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
        $session->user->setRole('admin');
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

    public function testSetPassword()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $newPassword = 'setByAdmin';
        $data = [
            'user' => $session1->user->getHandle(),
            'password' => $newPassword,
        ];

        $response = $this->app->call('PUT','/admin/password', $data, $session2->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $session1->user->loadFromId($session1->user->getId());
        $this->assertTrue($session1->user->checkPassword($newPassword));
    }
    
    public function testSetPasswordNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $newPassword = 'setByAdmin';
        $data = [
            'user' => $session1->user->getHandle(),
            'password' => $newPassword,
        ];

        $response = $this->app->call('PUT','/admin/password', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
        $session1->user->loadFromId($session1->user->getId());
        $this->assertFalse($session1->user->checkPassword($newPassword));
    }
    
    public function testSetAddress()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $newAddress = 'setByAdmin';
        $data = [
            'user' => $session1->user->getHandle(),
            'address' => $newAddress,
        ];

        $response = $this->app->call('PUT','/admin/address', $data, $session2->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $session1->user->loadFromId($session1->user->getId());
        $this->assertEquals($session1->user->getPatronAddress(), $newAddress);
    }
    
    public function testSetAddressNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $newAddress = 'setByAdmin';
        $data = [
            'user' => $session1->user->getHandle(),
            'address' => $newAddress,
        ];

        $response = $this->app->call('PUT','/admin/address', $data, $session2->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertNotEquals($session1->user->getPatronAddress(), $newAddress);
    }
    
    public function testSetBirthday()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $newDay = '17';
        $newMonth = '5';
        $data = [
            'user' => $session1->user->getHandle(),
            'day' => $newDay,
            'month' => $newMonth,
        ];

        $response = $this->app->call('PUT','/admin/birthday', $data, $session2->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $session1->user->loadFromId($session1->user->getId());
        $this->assertEquals($session1->user->getPatronBirthday(), "$newDay/$newMonth");
    }
    
    public function testSetBirthdayNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $newDay = '17';
        $newMonth = '5';
        $data = [
            'user' => $session1->user->getHandle(),
            'day' => $newDay,
            'month' => $newMonth,
        ];

        $response = $this->app->call('PUT','/admin/birthday', $data, $session2->token);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertNotEquals($session1->user->getPatronBirthday(), "$newDay/$newMonth");
    }
    
    public function testAddRemoveBadge()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $newBadge = 'found-bug';
        $data = [
            'user' => $session1->user->getHandle(),
            'badge' => $newBadge,
        ];

        $response = $this->app->call('POST','/admin/badge', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue($json->badges->{$newBadge});
    
        $response = $this->app->call('DELETE','/admin/badge', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
    
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue(!isset($json->badges->{$newBadge}));
    }
    
    public function testAddBadgeNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $newBadge = 'found-bug';
        $data = [
            'user' => $session1->user->getHandle(),
            'badge' => $newBadge,
        ];

        $response = $this->app->call('POST','/admin/badge', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
    }
    
    public function testMakePatron()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $tier = 8;
        $data = [
            'user' => $session1->user->getHandle(),
            'patron' => $tier,
        ];

        $response = $this->app->call('POST','/admin/patron', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->patron->tier, $tier);
        $session1->user->loadFromId($session1->user->getId());
        $this->assertEquals($session1->user->getPatronTier(), $tier);
    }
    
    public function testMakePatronNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $tier = 8;
        $data = [
            'user' => $session1->user->getHandle(),
            'patron' => $tier,
        ];

        $response = $this->app->call('POST','/admin/patron', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
    }
    
    public function testSendPatronEmail()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session1->user->setPatronTier(8);
        $session1->user->save();

        $data = [ 'user' => $session1->user->getHandle() ];

        $response = $this->app->call('POST','/admin/patron/email', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
    }
    
    public function testSendPatronEmailNoPatron()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $data = [ 'user' => $session1->user->getHandle() ];

        $response = $this->app->call('POST','/admin/patron/email', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'not-a-patron');
    }
    
    public function testSendPatronEmailNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $data = [ 'user' => $session1->user->getHandle() ];

        $response = $this->app->call('POST','/admin/patron/email', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
    }
    
    public function testAdminLoad()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);

        $data = [ 'user' => $session1->user->getHandle() ];

        $response = $this->app->call('GET','/admin/user/'.$session1->user->getHandle(), null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->account->id, $session1->user->getId());
        $this->assertEquals($json->account->handle, $session1->user->getHandle());
    }
    
    public function testAdminLoadNonAdmin()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        $session2->user->setRole('user');
        $session2->user->save();

        $data = [ 'user' => $session1->user->getHandle() ];

        $response = $this->app->call('GET','/admin/user/'.$session1->user->getHandle(), null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'access_denied');
    }
    
}
