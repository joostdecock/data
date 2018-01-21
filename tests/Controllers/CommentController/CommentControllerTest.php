<?php

namespace Freesewing\Data\Tests\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Draft;

class CommentControllerTest extends \PHPUnit\Framework\TestCase
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

    public function testCreateAndReply()
    {
        $session = $this->getSession();
        
        $data = [
            'page' => '/test',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        $data['parent'] = $json->id;
        $response = $this->app->call('POST','/comment', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
    }

    public function testReplyToOther()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        $data = [
            'page' => '/users/joost',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        $data['parent'] = $json->id;
        $response = $this->app->call('POST','/comment', $data, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
    }

    public function testRemove()
    {
        $session = $this->getSession();
        
        $data = [
            'page' => '/test',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        $response = $this->app->call('DELETE','/comment/'.$json->id, null, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->reason, 'comment_removed');
    }

    public function testRemoveOtherComment()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        $data = [
            'page' => '/test',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        $response = $this->app->call('DELETE','/comment/'.$json->id, null, $session2->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 400);
        $this->assertEquals($json->result, 'error');
        $this->assertEquals($json->reason, 'not_your_comment');
    }

    public function testEmailReply()
    {
        $session1 = $this->getSession(1);
        $session2 = $this->getSession(2);
        
        $data = [
            'page' => '/test',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session1->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        unset($data);
        $data = [
            'sender' => $session2->user->getEmail(),
            'subject' => 'Yada Yada replied to your comment [comment#'.$json->id.']',
            'stripped-text' => 'This is an email reply',
        ];

        $response = $this->app->call('POST','/email/comment', $data);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
    }

    public function testPageRecentComments()
    {
        $session = $this->getSession();
        
        $data = [
            'page' => '/test',
            'comment' => 'This is a comment',
        ];

        $response = $this->app->call('POST','/comment', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');
        $this->assertTrue(isset($json->id));
        
        $data['parent'] = $json->id; 
        $data['comment'] = 'This is a different comment';
        $response = $this->app->call('POST','/comment', $data, $session->token);
        $json = json_decode((string)$response->getBody());
        $commentId = $json->id;

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->message, 'comment/created');

        $response = $this->app->call('GET','/comments/page/test');
        $json = json_decode((string)$response->getBody());

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertTrue($json->count > 1);
        $comment = $json->comments->{$commentId};
        $this->assertTrue(is_object($comment));
        $this->assertEquals($comment->comment, 'This is a different comment');
        
        $response = $this->app->call('GET','/comments/recent/2');
        $json = json_decode((string)$response->getBody());
        
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json->result, 'ok');
        $this->assertEquals($json->count, 2);
        $this->assertTrue(is_object($comment));
        $this->assertEquals($comment->comment, 'This is a different comment');
    }

}
