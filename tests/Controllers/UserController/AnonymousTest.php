<?php

namespace Freesewing\Data\Tests\Controllers\UserController;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Mailgun\Mailgun;
use Mailgun\Api\Message;
use Freesewing\Data\Objects\User;

class AnonymousTest extends \PHPUnit\Framework\TestCase
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
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'signup_complete');
        $this->assertEquals($json->message, 'signup/success');
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
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_exists');
        $this->assertEquals($json->message, 'signup/account-exists');
    }

    public function testSignupNoEmail()
    {
        $data = [
            'signup-password' => 'bananas'
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'invalid_input');
        $this->assertEquals($json->message, 'generic/error');
    }

    public function testSignupNoPassword()
    {
        $data = [
            'signup-email' => time().'.testSignupNoPassword@freesewing.org',
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'invalid_input');
        $this->assertEquals($json->message, 'generic/error');
    }

    public function testSignupEmptyPassword()
    {
        $data = [
            'signup-email' => time().'.testSignupEmptyPassword@freesewing.org',
        ];

        $response = $this->app->call('POST','/signup', $data);
        
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'invalid_input');
        $this->assertEquals($json->message, 'generic/error');
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

    public function testConfirm()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testConfirm@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setPendingEmail(time().'.testConfirmPending@freesewing.org');
        $obj->setStatus('active');
        $obj->save();
        $response = $this->app->call('GET','/confirm/'.$obj->getHandle().'/'.$obj->getActivationToken());

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'confirm_complete');
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testConfirmInvalidUser()
    {
        $response = $this->app->call('GET','/confirm/inval/id');
        
        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'no_such_account');
        $this->assertEquals($json->message, 'activation/no-such-account');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testConfirmBlockedUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testConfirmBlocked@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setPendingEmail(time().'.testConfirmPending@freesewing.org');
        $obj->setStatus('blocked');
        $obj->save();
        $response = $this->app->call('GET','/confirm/'.$obj->getHandle().'/'.$obj->getActivationToken());

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'account/blocked');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testConfirmTokenMismatch()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testConfirmTokenMismatch@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setPendingEmail(time().'.testConfirmPending@freesewing.org');
        $obj->setStatus('active');
        $obj->save();
        $response = $this->app->call('GET','/confirm/'.$obj->getHandle().'/incorrect');

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'token_mismatch');
        $this->assertEquals($json->message, 'activation/token-mismatch');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testLogin()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testLogin@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('active');
        $obj->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];
        $response = $this->app->call('POST','/login', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'password_correct');
        $this->assertEquals($json->message, 'login/success');
        $this->assertTrue(isset($json->token));
        $this->assertEquals($json->userid, $obj->getId());
        $this->assertEquals($json->email, $email);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testLoginBlockedUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testLoginBlockedUser@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('blocked');
        $obj->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];
        $response = $this->app->call('POST','/login', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'login/account-blocked');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testLoginInactiveUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testLoginInactiveUser@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('inactive');
        $obj->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];
        $response = $this->app->call('POST','/login', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_inactive');
        $this->assertEquals($json->message, 'login/account-inactive');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testLoginWrongPassword()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testLoginWrongPassword@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('active');
        $obj->save();
        $data = [
            'login-email' => $email,
            'login-password' => 'peaches',
        ];
        $response = $this->app->call('POST','/login', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'login_failed');
        $this->assertEquals($json->message, 'login/failed');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testLoginUnknownUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testLoginUnknownUser@freesewing.org';
        $data = [
            'login-email' => $email,
            'login-password' => 'bananas',
        ];
        $response = $this->app->call('POST','/login', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'login_failed');
        $this->assertEquals($json->message, 'login/failed');
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testReset()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResetPassword@freesewing.org';
        $newPassword = 'peaches';
        $obj->create($email, 'bananas');
        $obj->setStatus('active');
        $obj->save();
        $data = [
            'reset-password' => $newPassword,
            'reset-handle' => $obj->getHandle(),
            'reset-token' => $obj->getResetToken(),
        ];
        $response = $this->app->call('POST','/reset', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'password_reset');
        $this->assertEquals($json->message, 'reset/success');
        // Load new password from DB
        $obj->loadFromId($obj->getId());
        $this->assertTrue($obj->checkPassword($newPassword));
    }

    public function testResetBlockedUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResetPasswordBlockedUser@freesewing.org';
        $newPassword = 'peaches';
        $obj->create($email, 'bananas');
        $obj->setStatus('blocked');
        $obj->save();
        $data = [
            'reset-password' => $newPassword,
            'reset-handle' => $obj->getHandle(),
            'reset-token' => $obj->getResetToken(),
        ];
        $response = $this->app->call('POST','/reset', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'reset/blocked');
        $this->assertFalse($obj->checkPassword($newPassword));
    }

    public function testResetInvalidUser()
    {
        $newPassword = 'peaches';
        $data = [
            'reset-password' => $newPassword,
            'reset-handle' => 'nopes',
            'reset-token' => 'invalid',
        ];
        $response = $this->app->call('POST','/reset', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'reset_failed');
        $this->assertEquals($json->message, 'reset/failed');
    }

    public function testResetInactiveUser()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testResetPasswordInactiveUser@freesewing.org';
        $newPassword = 'peaches';
        $obj->create($email, 'bananas');
        $obj->setStatus('inactive');
        $obj->save();
        $data = [
            'reset-password' => $newPassword,
            'reset-handle' => $obj->getHandle(),
            'reset-token' => $obj->getResetToken(),
        ];
        $response = $this->app->call('POST','/reset', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_inactive');
        $this->assertEquals($json->message, 'reset/inactive');
        $this->assertFalse($obj->checkPassword($newPassword));
    }

    public function testRecover()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testRecoverPassword@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('active');
        $obj->save();
        $data = [ 'recover-email' => $email ];
        $response = $this->app->call('POST','/recover', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'recover_initiated');
        $this->assertEquals($json->message, 'recover/sent');
    }

    public function testRecoverBlocked()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testRecoverPasswordBlocked@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('blocked');
        $obj->save();
        $data = [ 'recover-email' => $email ];
        $response = $this->app->call('POST','/recover', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_blocked');
        $this->assertEquals($json->message, 'recover/blocked');
    }

    public function testRecoverInactive()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testRecoverPasswordInactive@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->setStatus('inactive');
        $obj->save();
        $data = [ 'recover-email' => $email ];
        $response = $this->app->call('POST','/recover', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'account_inactive');
        $this->assertEquals($json->message, 'recover/inactive');
    }

    public function testRecoverInvalid()
    {
        $email = time().'.testRecoverPasswordInvalid@freesewing.org';
        $data = [ 'recover-email' => $email ];
        $response = $this->app->call('POST','/recover', $data);

        $json = json_decode((string)$response->getBody());
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'recover_failed');
        $this->assertEquals($json->message, 'recover/failed');
    }
}
