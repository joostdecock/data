<?php

namespace Freesewing\Data\Tests\Tools;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Tools\AvatarKit;

class AvatarKitTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    private function loadFixture($fixture)
    {
        $dir = __DIR__.'/../fixtures';
        $file = "$dir/JsonStore.$fixture.data";
        return file_get_contents($file);
    }

    public function testGetDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $this->assertEquals($obj->getDir('handl'), '/users/h/handl/account');
    }

    public function testGetDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $this->assertEquals($obj->getDir('handl', 'model', 'queen'), '/users/h/handl/models/queen');
    }

    public function testGetDiskDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['storage']['static_path'];
        $this->assertEquals($obj->getDiskDir('handl'), "$static/users/h/handl/account");
    }

    public function testGetDiskDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['storage']['static_path'];
        $this->assertEquals($obj->getDiskDir('handl', 'model', 'queen'), "$static/users/h/handl/models/queen");
    }

    public function testGetWebDirUser()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['app']['static_path'];
        $this->assertEquals($obj->getWebDir('handl'), "$static/users/h/handl/account");
    }

    public function testGetWebDirModel()
    {
        $obj = new AvatarKit($this->app->getContainer());

        $static = $this->app->getContainer()['settings']['app']['static_path'];
        $this->assertEquals($obj->getWebDir('handl', 'model', 'queen'), "$static/users/h/handl/models/queen");
    }

    public function testCreate()
    {
        $obj = new AvatarKit($this->app->getContainer());
        $file = $obj->create('handl');
        
        $this->assertEquals($file, 'handl.svg');
        $this->assertTrue(file_exists($obj->getDiskDir('handl')));
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
