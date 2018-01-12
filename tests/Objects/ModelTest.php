<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\JsonStore;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\User;

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
        $obj = new Model($this->app->getContainer());
        $json = new JsonStore();

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
        $obj = new Model($this->app->getContainer());
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
            ['Units', 'imperial'],
            ['Picture', 'avatar.jpg'],
            ['Units', 'imperial'],
        ];
    }

    public function testSetUnitsIncorrectly()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testSetUnitsIncorrectly@freesewing.org';
        $user->create($email, 'bananas');

        $obj->create($user);
        $obj->setUnits('kiwis');
        $obj->save();

        $this->assertEquals($obj->getUnits(), 'metric');
    }

    public function testSetMeasurement()
    {
        $obj = new Model($this->app->getContainer());
        
        $obj->setMeasurement('chestCircumference', '111');
        $this->assertEquals($obj->getMeasurement('chestCircumference'),111);
        $obj->setMeasurement('inseam', '101');
        $this->assertEquals($obj->getMeasurement('inseam'),101);
        $obj->setMeasurement('inseam', '102');
        $this->assertEquals($obj->getMeasurement('inseam'),102);
    }

    public function testCreate()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testCreateModel@freesewing.org';
        $now = date('Y-m-d H:i');
        $user->create($email, 'bananas');

        $obj->create($user);
        
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
        $this->assertEquals(substr($obj->getCreated(), 0, 16), $now);
    }
    
    public function testLoadFromId()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testLoadModelFromId@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj->create($user);
        $id = $obj->getId();
        unset($obj);

        $obj = new Model($this->app->getContainer());
        $obj->loadFromId($id);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testGetMeasurements()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testModelGetMeasurements@freesewing.org';
        $user->create($email, 'bananas');

        $obj->setMeasurement('chestCircumference', 100);
        $obj->setMeasurement('neckCircumference', 42);

        $obj->create($user);

        $check = new \stdClass();
        $check->chestCircumference = 100;
        $check->neckCircumference = 42;

        $this->assertEquals($obj->getMeasurements(), $check);
    }
    
    public function testGetDataAsJson()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testLoadModelFromHandle@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj->create($user);
        $handle = $obj->getHandle();
        unset($obj);

        $obj = new Model($this->app->getContainer());
        $obj->loadFromHandle($handle);

        $json = $obj->getDataAsJson();
        $this->assertTrue(is_string($json));
        $this->assertTrue(is_object(json_decode($json)));
    }
    
    public function testSetData()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testModelSetData@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj->create($user);

        $check = [
            'twitter' => 'freesewing_org',
            'instagram' => 'joostdecock',
            'github' => 'freesewing'
        ];
        
        $obj = new Model($this->app->getContainer());
        $obj->setData(json_encode($check));

        $this->assertEquals(json_decode(json_encode($obj->getData())), json_decode($obj->getDataAsJson()));
    }
    
    public function testLoadFromHandle()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testModelDataAsJson@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj->create($user);
        $handle = $obj->getHandle();
        unset($obj);

        $obj = new Model($this->app->getContainer());
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
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to remove a model
        $user = new User($this->app->getContainer());
        $email = time().'.testRemoveModel@freesewing.org';
        $user->create($email, 'bananas');

        $id = $obj->getId();
        $obj->remove($user);

        $this->assertFalse($obj->loadFromId($id));
    }
}
