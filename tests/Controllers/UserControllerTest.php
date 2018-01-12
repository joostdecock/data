<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Mailgun\Mailgun;
use Mailgun\Api\Message;
use Freesewing\Data\Objects\User;

class UserControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testSignup()
    {
        $data = [
            'signup-email' => time().'.testSignup@freesewing.org',
            'signup-password' => 'bananas'
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->saveFixture('signup',$body);
        $this->assertEquals($body,$this->loadFixture('signup'));
    }

    public function testSignupExistingAddress()
    {
        $data = [
            'signup-email' => time().'.testSignupExisting@freesewing.org',
            'signup-password' => 'bananas'
        ];

        $response = $this->app->call('POST','/signup', $data);
        unset($response);
        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->saveFixture('signup.existing',$body);
        $this->assertEquals($body,$this->loadFixture('signup.existing'));
    }

    public function testSignupNoEmail()
    {
        $data = [
            'signup-password' => 'bananas'
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->saveFixture('signup.noEmail',$body);
        $this->assertEquals($body,$this->loadFixture('signup.noEmail'));
    }

    public function testSignupNoPassword()
    {
        $data = [
            'signup-email' => time().'.testSignupNoPassword@freesewing.org',
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->saveFixture('signup.noPassword',$body);
        $this->assertEquals($body,$this->loadFixture('signup.noPassword'));
    }

    public function testSignupEmptyPassword()
    {
        $data = [
            'signup-email' => time().'.testSignupEmptyPassword@freesewing.org',
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $body = (string)$response->getBody();
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->saveFixture('signup.emptyPassword',$body);
        $this->assertEquals($body,$this->loadFixture('signup.emptyPassword'));
    }

    public function testActivate()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testActivate@freesewing.org';
        $obj->create($email, 'bananas');
        
        $response = $this->app->call('GET','/activate/'.$obj->getHandle().'/'.$obj->getActivationToken());
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'signup_complete');
        $this->assertEquals($json->message, 'login/success');
        $this->assertTrue(isset($json->token));
        $this->assertEquals($json->userid, $obj->getId());
        $this->assertEquals($json->email, $email);
        $this->assertEquals($json->username, 'user '.$obj->getId());
        $this->assertEquals($response->getStatusCode(), 200);
    }
    
    public function testActivateUnknownUser()
    {
        $response = $this->app->call('GET','/activate/unknown/user');
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'no_such_account');
        $this->assertEquals($json->message, 'activation/no-such-account');
        $this->assertEquals($response->getStatusCode(), 404);
    }
    
    public function testActivateBlockedUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testActivateBlockedUser@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('blocked');
        $obj->save();
        
        $response = $this->app->call('GET','/activate/'.$obj->getHandle().'/'.$obj->getActivationToken());
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'account/blocked');
        $this->assertEquals($response->getStatusCode(), 400);
    }
    
    public function testActivateTokenMismatch()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testActivateTokenMismatch@freesewing.org';
        $obj->create($email, 'bananas');
        
        $response = $this->app->call('GET','/activate/'.$obj->getHandle().'/thisisnogood');
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'token_mismatch');
        $this->assertEquals($json->message, 'activation/token-mismatch');
        $this->assertEquals($response->getStatusCode(), 400);
    }
    
    public function testResend()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResend@freesewing.org';
        $obj->create($email, 'bananas');
        
        $response = $this->app->call('POST','/resend', ['resend-email' => $email]);
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'signup_complete');
        $this->assertEquals($json->message, 'signup/success');
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testResendNonexistingUser()
    {
        $email = time().'.testResendNonexistingUser@freesewing.org';
        
        $response = $this->app->call('POST','/resend', ['resend-email' => $email]);
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'no_such_account');
        $this->assertEquals($json->message, 'resend/no-such-account');
        $this->assertEquals($response->getStatusCode(), 404);
    }
    
    public function testResendBlockedUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResendBlockedUser@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('blocked');
        $obj->save();
        
        $response = $this->app->call('POST','/resend', ['resend-email' => $email]);
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'resend/account-blocked');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testResendActiveUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResendActiveUser@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('active');
        $obj->save();
        
        $response = $this->app->call('POST','/resend', ['resend-email' => $email]);
        
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_active');
        $this->assertEquals($json->message, 'resend/account-active');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    private function loadFixture($fixture)
    {
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/UserController.$fixture.data";
        return file_get_contents($file);
    }

    private function saveFixture($fixture, $data)
    {
//        return true;
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/UserController.$fixture.data";
        $f = fopen($file,'w');
        fwrite($f,$data);
        fclose($f);
    }
}
