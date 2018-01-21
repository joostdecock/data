<?php

namespace Freesewing\Data\Tests\Tools;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Tools\MailKit;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Comment;

class MailKitTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }
    
    public function testSignUp()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user1 = new User($this->app->getContainer());
        $email1 = time().'.testMailKitSignup@freesewing.org';
        $user1->create($email1, 'bananas');
        $user2 = new User($this->app->getContainer());
        $email2 = time().'.testMailKitSignup@hotmail.com';
        $user2->create($email2, 'bananas');
        
        $this->assertTrue($obj->signUp($user1));
        $this->assertTrue($obj->signUp($user2));
    }

    public function testPatronA()
    {
        $obj = new MailKit($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitPatron2@freesewing.org';
        $user->create($email, 'bananas');
        $user->setPatronTier(2);
        $this->assertTrue($obj->patron($user));
    }

    public function testPatronB()
    {
        $obj = new MailKit($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitPatron4@hotmail.com';
        $user->create($email, 'bananas');
        $user->setPatronTier(4);
        $user->setPatronAddress('Some address somewhere');
        $this->assertTrue($obj->patron($user));
    }

    public function testPatronC()
    {
        $obj = new MailKit($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitPatron8@hotmail.com';
        $user->create($email, 'bananas');
        $user->setPatronTier(8);
        $user->setPatronAddress('Some other address somewhere');
        $this->assertTrue($obj->patron($user));
    }

    public function testPatronD()
    {
        $obj = new MailKit($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitPatron8NoAddress@hotmail.com';
        $user->create($email, 'bananas');
        $user->setPatronTier(8);
        $this->assertTrue($obj->patron($user));
    }
    
    public function testEmailChange()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user1 = new User($this->app->getContainer());
        $email1 = time().'.testMailKitEmailChange@freesewing.org';
        $user1->create($email1, 'bananas');
        $user2 = new User($this->app->getContainer());
        $email2 = time().'.testMailKitEmailChange@hotmail.com';
        $user2->create($email2, 'bananas');
        
        $this->assertTrue($obj->emailChange($user1, 'joost@decock.org'));
        $this->assertTrue($obj->emailChange($user2, 'joost@decock.org'));
    }

    public function testRecover()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user1 = new User($this->app->getContainer());
        $email1 = time().'.testMailKitRecover@freesewing.org';
        $user1->create($email1, 'bananas');
        $user2 = new User($this->app->getContainer());
        $email2 = time().'.testMailKitRecover@hotmail.com';
        $user2->create($email2, 'bananas');
        
        $this->assertTrue($obj->recover($user1));
        $this->assertTrue($obj->recover($user2));
    }

    public function testGoodbye()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user1 = new User($this->app->getContainer());
        $email1 = time().'.testMailKitGoodbye@freesewing.org';
        $user1->create($email1, 'bananas');
        $user2 = new User($this->app->getContainer());
        $email2 = time().'.testMailKitGoodbye@hotmail.com';
        $user2->create($email2, 'bananas');
        
        $this->assertTrue($obj->goodbye($user1));
        $this->assertTrue($obj->goodbye($user2));
    }

    public function testCommentNotify()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitCommentNotify@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a parent comment and a reply
        $comment1 = new Comment($this->app->getContainer());
        $comment1->setComment("This is a **test** comment");
        $comment1->setPage('/unit/test');
        $id = $comment1->create($user);
        $comment2 = new Comment($this->app->getContainer());
        $comment2->setComment("This is a **test** comment");
        $comment2->setPage('/unit/test');
        $comment2->setParent($id);
        $comment2->create($user);

        $this->assertTrue($obj->commentNotify($user, $comment1, $user ,$comment2));
    }

    public function testProfileCommentNotify()
    {
        $obj = new MailKit($this->app->getContainer());

        // We need a User object
        $user = new User($this->app->getContainer());
        $email = time().'.testMailKitProfileCommentNotify@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a comment
        $comment = new Comment($this->app->getContainer());
        $comment->setComment("This is a **test** comment");
        $comment->setPage('/unit/test');

        $this->assertTrue($obj->profileCommentNotify($user, $comment, $user));
    }

}
