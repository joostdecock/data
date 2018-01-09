<?php

namespace App\Tests;

use App\Tests\TestApp;

class ModelTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        $json = new \App\Data\JsonStore();

        $this->assertEquals($obj->getData(),$json);
    }

    /**
     * @param string $methodSuffix The part of the method to call without 'get' or 'set'
     * @param $expectedResult Result to check for
     *
     * @dataProvider providerGettersReturnWhatSettersSet
     */
    public function testGettersReturnWhatSettersSet($methodSuffix, $expectedResult)
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['Name', 'Test Name'],
            ['Notes', "These are the notes, they support **Markdown**\n\nNew paragraph"],
            ['Body', 'female'],
            ['Migrated', '1'],
            ['Shared', '0'],
            ['Picture', 'avatar.jpg'],
            ['Units', 'imperial'],
        ];
    }

    public function testSetMeasurement()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        
        $obj->setMeasurement('chestCircumference', '111');
        $this->assertEquals($obj->getMeasurement('chestCircumference'),111);
        $obj->setMeasurement('inseam', '101');
        $this->assertEquals($obj->getMeasurement('inseam'),101);
        $obj->setMeasurement('inseam', '102');
        $this->assertEquals($obj->getMeasurement('inseam'),102);
    }

    public function testCreate()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($this->app->getContainer());
        $email = time().'.testCreateModel@freesewing.org';
        $user->create($email, 'boobies');

        $obj->create($user);
        
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testLoadFromId()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($this->app->getContainer());
        $email = time().'.testLoadModelFromId@freesewing.org';
        $user->create($email, 'boobies');
        
        $obj->create($user);
        $id = $obj->getId();
        unset($obj);

        $obj = new \App\Data\Model($this->app->getContainer());
        $obj->loadFromId($id);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testLoadFromHandle()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($this->app->getContainer());
        $email = time().'.testLoadModelFromHandle@freesewing.org';
        $user->create($email, 'boobies');
        
        $obj->create($user);
        $handle = $obj->getHandle();
        unset($obj);

        $obj = new \App\Data\Model($this->app->getContainer());
        $obj->loadFromHandle($handle);
        $this->assertEquals($obj->getHandle(), $handle);
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testRemove()
    {
        $obj = new \App\Data\Model($this->app->getContainer());
        
        // We need a user object to remove a model
        $user = new \App\Data\User($this->app->getContainer());
        $email = time().'.testRemoveModel@freesewing.org';
        $user->create($email, 'boobies');

        $id = $obj->getId();
        $obj->remove($user);

        $this->assertFalse($obj->loadFromId($id));
    }
}
