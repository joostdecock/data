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

    public function testCommentProperties()
    {
        $obj = new \App\Data\Comment($this->app->getContainer());
        $obj->setUser(3);
        $obj->setComment("This is a **test** comment");
        $obj->setPage('/unit/test');
        $obj->setParent(2);
        $obj->setStatusActive();

        $this->assertEquals($obj->getUser(),3);
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

}
