<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Draft;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\JsonStore;

class DraftTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        $obj = new Draft($this->app->getContainer());
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
        $obj = new Draft($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['User', 2],
            ['Model', 3],
            ['Pattern', 'TrayvonTie'],
            ['Handle', 'testy'],
            ['Name', 'Test draft'],
            ['Svg', '<svg></svg>'],
            ['Compared', '<svg class="compare"></svg>'],
            ['Notes', 'These are my notes'],
            ['Shared', 1],
            ['CoreUrl', 'some long url here'],
            ['Units', 'imperial'],
            ['Version', 'test1.0'],
        ];
    }

    public function testCreate()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraft@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $now = date('Y-m-d H:i');
        $id = $obj->create($data, $user,$model);
        $obj->setOption('fries', 'loads');

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals($obj->data->getNode('measurements.neckCircumference'), 42);
        $this->assertEquals(substr($obj->getCreated(), 0, 16), $now);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getMeasurement('centerbackneckToWaist'), 52);
        $this->assertEquals($obj->getOption('fries'), 'loads');
    }

    public function testCreateMixedUnitsImperial()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraftMixedUnitsImperial@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 20);
        $model->setMeasurement('neckCircumference', 16);
        $model->setUnits('imperial');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(floor($obj->data->getNode('measurements.neckCircumference')), 40);
    }
    
    public function testCreateMixedUnitsMetric()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraftMixedUnitsMetric@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'imperial',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(floor($obj->data->getNode('measurements.neckCircumference')), 16);
    }

    public function testGetOptions()
    {
        $obj = new Draft($this->app->getContainer());
        
        $obj->data->setNode('options.test1','value1');
        $obj->data->setNode('options.test2','value2');
        
        $this->assertEquals($obj->getOption('test1'), 'value1');
        $this->assertEquals($obj->getOption('test2'), 'value2');
        $this->assertEquals($obj->getOptions(), (object)['test1' => 'value1', 'test2' => 'value2']);
    }
    
    public function testGetMeasurements()
    {
        $obj = new Draft($this->app->getContainer());
        
        $obj->data->setNode('measurements.test1','value1');
        $obj->data->setNode('measurements.test2','value2');
        
        $this->assertEquals($obj->getMeasurement('test1'), 'value1');
        $this->assertEquals($obj->getMeasurement('test2'), 'value2');
        $this->assertEquals($obj->getMeasurements(), (object)['test1' => 'value1', 'test2' => 'value2']);
    }
    
    public function testSetData()
    {
        $data = new JsonStore($this->app->GetContainer()); 
        $data->setNode('measurements.test1','value1');
        $data->setNode('measurements.test2','value2');
        $data->setNode('options.test1','value1');
        $data->setNode('options.test2','value2');
        
        $obj = new Draft($this->app->getContainer());
        $obj->setData($data);

        $this->assertEquals($obj->getData(), $data);
    }
    
    public function testLoadFromHandle()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testLoadDraftFromHandle@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $now = date('Y-m-d H:i');
        $id = $obj->create($data, $user,$model);
        $handle = $obj->getHandle();

        unset($obj);
        $obj = new Draft($this->app->getContainer());
        $obj->loadFromHandle($handle);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals($obj->data->getNode('measurements.neckCircumference'), 42);
        $this->assertEquals(substr($obj->getCreated(), 0, 16), $now);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getMeasurement('centerbackneckToWaist'), 52);
    }
    
    public function testRecreate()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testRecreateDraft@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $now = date('Y-m-d H:i');
        $id = $obj->recreate($data, $user,$model);
        
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('measurements.neckCircumference'), 42);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(substr($obj->getCreated(), 0, 16), $now);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getMeasurement('centerbackneckToWaist'), 52);
    }

    public function testRecreateMixedUnits()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testRecreateDraftMixedUnits@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'imperial',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $now = date('Y-m-d H:i');
        $id = $obj->recreate($data, $user,$model);
        
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals(floor($obj->data->getNode('measurements.neckCircumference')), 6);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(substr($obj->getCreated(), 0, 16), $now);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals(floor($obj->getMeasurement('centerbackneckToWaist')), 8);
    }

    public function testRemove()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testRemoveDraft@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $id = $obj->create($data, $user,$model);
        $handle = $obj->getHandle();
        $dir = $this->app->getContainer()['settings']['storage']['static_path'];
        $dir .= '/users/'.substr($user->getHandle(),0,1).'/'.$user->getHandle().'/drafts/'.$handle.'/';
        
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_file("$dir/$handle.svg"));
        $this->assertTrue(is_file("$dir/$handle.compared.svg"));
        $obj->remove($user);
        $this->assertFalse(is_dir($dir));
        $this->assertFalse($obj->loadFromId($id));
        $this->assertFalse($obj->loadFromHandle($handle));
    }

    public function testExport()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testExportDraft@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);
        
        $this->assertTrue(file_exists($obj->export($user, 'pdf', 'Trayvon', $obj->getHandle())));
        $this->assertTrue(file_exists($obj->export($user, 'letter.pdf', 'Trayvon', $obj->getHandle())));
        $this->assertTrue(file_exists($obj->export($user, 'tabloid.pdf', 'Trayvon', $obj->getHandle())));
        $this->assertTrue(file_exists($obj->export($user, 'a4.pdf', 'Trayvon', $obj->getHandle())));
    }

    private function loadFixture($fixture)
    {
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/JsonStore.$fixture.data";
        return file_get_contents($file);
    }

    private function saveFixture($fixture, $data)
    {
        return true;
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/JsonStore.$fixture.data";
        $f = fopen($file,'w');
        fwrite($f,$data);
        fclose($f);
    }
}
