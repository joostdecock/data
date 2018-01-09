<?php

namespace App\Tests;

use App\Tests\TestApp;

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
        $this->assertClassHasAttribute($attribute, '\App\Data\Comment');
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
        $obj = new \App\Data\Comment($this->app->getContainer());
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
        $obj = new \App\Data\Comment($this->app->getContainer());
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
        $obj = new \App\Data\Comment($this->app->getContainer());
        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setParent(2);
        $obj->setStatusActive();

        // We need a user object to save a comment
        $user = new \App\Data\User($this->app->getContainer());
        $email = time().'.testSaveComment@freesewing.org';
        $user->create($email, 'boobies');
        $id = $user->getId();
        $obj->create($user);

        $this->assertEquals($obj->getUser(),$id);
        $this->assertEquals($obj->getComment(),"This is a **test** comment");
        $this->assertEquals($obj->getPage(),'/unit/test');
        $this->assertEquals($obj->getStatus(),'active');
        
    }

    public function testSetStatus()
    {
        $obj = new \App\Data\Comment($this->app->getContainer());
        $obj->setStatusActive();
        $this->assertEquals($obj->getStatus(),'active');
        $obj->setStatusRemoved();
        $this->assertEquals($obj->getStatus(),'removed');
        $obj->setStatusRestricted();
        $this->assertEquals($obj->getStatus(),'restricted');
        $obj->setStatusRemoved();
        $this->assertEquals($obj->getStatus(),'removed');
    }

}
