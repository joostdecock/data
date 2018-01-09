<?php

namespace App\Tests;

use \Slim\Container;
require_once __DIR__.'/../assets/SlimTest.php';

class ModelTest extends \PHPUnit\Framework\TestCase
{

    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        $app = SlimTest::bootstrap();

        $obj = new \App\Data\Model($app->getContainer());
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
        $app = SlimTest::bootstrap();

        $obj = new \App\Data\Model($app->getContainer());
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
        $app = SlimTest::bootstrap();
        $obj = new \App\Data\Model($app->getContainer());
        
        $obj->setMeasurement('chestCircumference', '111');
        $this->assertEquals($obj->getMeasurement('chestCircumference'),111);
        $obj->setMeasurement('inseam', '101');
        $this->assertEquals($obj->getMeasurement('inseam'),101);
        $obj->setMeasurement('inseam', '102');
        $this->assertEquals($obj->getMeasurement('inseam'),102);
    }

    public function testCreate()
    {
        $app = SlimTest::bootstrap();
        $obj = new \App\Data\Model($app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($app->getContainer());
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
        $app = SlimTest::bootstrap();
        $obj = new \App\Data\Model($app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($app->getContainer());
        $email = time().'.testLoadModelFromId@freesewing.org';
        $user->create($email, 'boobies');
        
        $obj->create($user);
        $id = $obj->getId();
        unset($obj);

        $obj = new \App\Data\Model($app->getContainer());
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
        $app = SlimTest::bootstrap();
        $obj = new \App\Data\Model($app->getContainer());
        
        // We need a user object to create a model
        $user = new \App\Data\User($app->getContainer());
        $email = time().'.testLoadModelFromHandle@freesewing.org';
        $user->create($email, 'boobies');
        
        $obj->create($user);
        $handle = $obj->getHandle();
        unset($obj);

        $obj = new \App\Data\Model($app->getContainer());
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
        $app = SlimTest::bootstrap();
        $obj = new \App\Data\Model($app->getContainer());
        
        // We need a user object to remove a model
        $user = new \App\Data\User($app->getContainer());
        $email = time().'.testRemoveModel@freesewing.org';
        $user->create($email, 'boobies');

        $id = $obj->getId();
        $obj->remove($user);

        $this->assertFalse($obj->loadFromId($id));
    }
}
