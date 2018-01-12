<?php

namespace Freesewing\Data\Tests\Data;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Comment;
use Freesewing\Data\Objects\User;

class CommentTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /**
     * @param string $attribute Attribute to check for
     *
     * @dataProvider providerTestAttributeExists
     */
    public function testAttributeExists($attribute)
    {
        $this->assertClassHasAttribute($attribute, '\Freesewing\Data\Objects\Comment');
    }

    public function providerTestAttributeExists()
    {
        return [
            ['container'],
            ['id'],
            ['user'],
            ['comment'],
            ['page'],
            ['time'],
            ['status'],
        ];
    }

    /**
     * @param string $methodSuffix The part of the method to call without 'get' or 'set'
     * @param $expectedResult Result to check for
     *
     * @dataProvider providerGettersReturnWhatSettersSet
     */
    public function testGettersReturnWhatSettersSet($methodSuffix, $expectedResult)
    {
        $obj = new Comment($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['User', 2],
            ['Comment', "This is a **test** comment"],
            ['Page', '/unit/test'],
            ['Parent', 3],
        ];
    }
    
    public function testCommentProperties()
    {
        $obj = new Comment($this->app->getContainer());
        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setParent(2);
        $obj->setStatusActive();

        $this->assertEquals($obj->getComment(),"This is a **test** comment");
        $this->assertEquals($obj->getPage(),'/unit/test');
        $this->assertEquals($obj->getStatus(),'active');
        
        $obj->setStatusRemoved();
        $this->assertEquals($obj->getStatus(),'removed');
        $obj->setStatusRestricted();
        $this->assertEquals($obj->getStatus(),'restricted');
    }

    public function testSaveComment()
    {
        $obj = new Comment($this->app->getContainer());
        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setParent(2);
        $obj->setStatusActive();

        // We need a user object to save a comment
        $user = new User($this->app->getContainer());
        $email = time().'.testSaveComment@freesewing.org';
        $user->create($email, 'bananas');
        $id = $user->getId();
        $obj->create($user);

        $this->assertEquals($obj->getUser(),$id);
        $this->assertEquals($obj->getComment(),"This is a **test** comment");
        $this->assertEquals($obj->getPage(),'/unit/test');
        $this->assertEquals($obj->getStatus(),'active');
        
    }

    public function testSetStatus()
    {
        $obj = new Comment($this->app->getContainer());
        $obj->setStatusActive();
        $this->assertEquals($obj->getStatus(),'active');
        $obj->setStatusRemoved();
        $this->assertEquals($obj->getStatus(),'removed');
        $obj->setStatusRestricted();
        $this->assertEquals($obj->getStatus(),'restricted');
        $obj->setStatusRemoved();
        $this->assertEquals($obj->getStatus(),'removed');
    }

    public function testCreate()
    {
        $obj = new Comment($this->app->getContainer());
        
        // We need a user object to create a comment
        $user = new User($this->app->getContainer());
        $email = time().'.testCreateComment@freesewing.org';
        $user->create($email, 'bananas');

        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setParent(2);
        $obj->create($user);
        
        $this->assertEquals($obj->getUser(),$user->getId());
        $this->assertEquals($obj->getComment(),"This is a **test** comment");
        $this->assertEquals($obj->getPage(),'/unit/test');
        $this->assertEquals($obj->getStatus(),'active');
        $this->assertEquals($obj->getParent(),2);
    }
    
    public function testLoad()
    {
        $obj1 = new Comment($this->app->getContainer());
        
        // We need a user object to create a comment
        $user = new User($this->app->getContainer());
        $email = time().'.testLoadComment@freesewing.org';
        $user->create($email, 'bananas');

        $obj1->setComment("This is a **test** comment");
        $obj1->setPage('/unit/test');
        $obj1->setParent(2);
        $obj1->create($user);
        $id = $obj1->getId();

        unset($obj1);
        
        $obj = new Comment($this->app->getContainer());
        $obj->load($id);

        $this->assertEquals($obj->getUser(),$user->getId());
        $this->assertEquals($obj->getComment(),"This is a **test** comment");
        $this->assertEquals($obj->getPage(),'/unit/test');
        $this->assertEquals($obj->getStatus(),'active');
        $this->assertEquals($obj->getParent(),2);
    }
    
    public function testRemove()
    {
        $obj = new Comment($this->app->getContainer());
        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setStatusActive();

        // We need a user object to remove a comment
        $user = new User($this->app->getContainer());
        $email = time().'.testRemoveComment@freesewing.org';
        $user->create($email, 'bananas');
        $obj->create($user);
        

        $id = $obj->getId();
        $obj->remove();

        $this->assertFalse($obj->load($id));
    }

    public function testHasChildren()
    {
        $obj1 = new Comment($this->app->getContainer());
        $obj2 = new Comment($this->app->getContainer());
        $obj3 = new Comment($this->app->getContainer());
        
        // We need a user object to create a comment
        $user = new User($this->app->getContainer());
        $email = time().'.testCommentHasChildren@freesewing.org';
        $user->create($email, 'bananas');

        $obj1->setComment("This is a **test** comment");
        $obj1->setPage('/unit/test');
        $obj1->create($user);

        $id = $obj1->getId();
        
        $obj2->setComment("This is a **test** reply");
        $obj2->setPage('/unit/test');
        $obj2->setParent($id);
        $obj2->create($user);

        $obj1->remove();
        $obj3->load($id);
        $this->assertEquals($obj3->getUser(),$user->getId());
        $this->assertEquals($obj3->getComment(),"This is a **test** comment");
        $this->assertEquals($obj3->getPage(),'/unit/test');
        $this->assertEquals($obj3->getStatus(),'removed');
    }
}
