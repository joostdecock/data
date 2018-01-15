<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\JsonStore;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Draft;

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
        $user->create($email, 'bananas');

        $obj->create($user);
        
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getShared(), 0);
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
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testGetMeasurements()
    {
        $obj = new Model($this->app->getContainer());
        
        // We need a user object to create a model
        $user = new User($this->app->getContainer());
        $email = time().'.testModelGetMeasurements@freesewing.org';
        $user->create($email, 'bananas');

        $obj->create($user);
        $obj->setMeasurement('chestCircumference', 100);
        $obj->setMeasurement('neckCircumference', 42);
        $obj->save();


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
        $data = new JsonStore($this->app->GetContainer()); 
        $data->setNode('measurements.test1','value1');
        $data->setNode('measurements.test2','value2');
        $data->setNode('options.test1','value1');
        $data->setNode('options.test2','value2');
        
        $obj = new Model($this->app->getContainer());
        $obj->setData($data);

        $this->assertEquals($obj->getData(), $data);
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
    
    public function testGetDrafts()
    {
        // We need a User object
        $user = new User($this->app->getContainer());
        $email = time().'.testGetDraftsForModel@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj = new Model($this->app->getContainer());
        $obj->create($user);
        $obj->setMeasurement('centerbackneckToWaist', 52);
        $obj->setMeasurement('neckCircumference', 42);
        $obj->setUnits('metric');

        // Draft 1
        $draft1 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];
        $draft1->create($data, $user, $obj);
        
        // Draft 2
        $draft2 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Paperless',
            'pattern' => 'TrayvonTie',
        ];
        $draft2->create($data, $user, $obj);
        
        $drafts = $obj->getDrafts();
        
        $d1 = $drafts[$draft1->getId()];
        $d2 = $drafts[$draft2->getId()];

        $this->assertTrue(is_object($d1));
        $this->assertTrue(is_object($d2));
        $this->assertEquals($d1->pattern, 'TrayvonTie');
        $this->assertEquals($d2->id, $draft2->getId());
    }
    
    public function testExport()
    {
        // We need a User object
        $user = new User($this->app->getContainer());
        $email = time().'.testExportModel@freesewing.org';
        $user->create($email, 'bananas');
        
        $obj = new Model($this->app->getContainer());
        $obj->create($user);
        $obj->setMeasurement('centerbackneckToWaist', 52);
        $obj->setMeasurement('neckCircumference', 42);
        $obj->setUnits('metric');

        $path = $obj->export();
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','', $path);
        
        $this->assertTrue(is_file($path.'/'.$obj->getHandle().'.csv'));
        $this->assertTrue(is_file($path.'/'.$obj->getHandle().'.json'));
        $this->assertTrue(is_file($path.'/'.$obj->getHandle().'.yaml'));
    }
    
}
