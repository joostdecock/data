<?php

namespace App\Tests;

use App\Tests\TestApp;

class JsonStoreTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** 
     * Tests setting and retrieving of JSON data
     */
    public function testSetAndGetAndUnsetJsonData()
    {
        $obj = new \App\Data\JsonStore();
        $obj->setNode('top', 'level');
        $this->assertEquals($obj->getNode('top'),'level');
        
        $obj->setNode('this.is.a.test', 'foo');
        $this->assertEquals($obj->getNode('this.is.a.test'),'foo');
        
        $obj->setNode('this.is.another.test', 'bar');
        $this->assertEquals($obj->getNode('this.is.a.test'),'foo');
        $this->assertEquals($obj->getNode('this.is.another.test'),'bar');
        
        $this->saveFixture('json1',(string)$obj);
        $this->assertEquals((string)$obj,$this->loadFixture('json1'));

        $obj->unsetNode('this.is.another.test');
        $this->assertFalse($obj->getNode('this.is.another.test'));

        $this->saveFixture('json2',(string)$obj);
        $this->assertEquals((string)$obj,$this->loadFixture('json2'));

        $obj->unsetNode('this.is.a.test');
        $this->assertFalse($obj->getNode('this.is.a.test'));
        
        $this->saveFixture('json3',(string)$obj);
        $this->assertEquals((string)$obj,$this->loadFixture('json3'));
        
        $obj->unsetNode('top');
        $this->assertFalse($obj->getNode('top'));
        
        $this->saveFixture('json4',(string)$obj);
        $this->assertEquals((string)$obj,$this->loadFixture('json4'));
        
        $obj->unsetNode('this');
        $this->assertFalse($obj->getNode('this'));
        
        $this->saveFixture('json5',(string)$obj);
        $this->assertEquals((string)$obj,$this->loadFixture('json5'));
    }

    private function loadFixture($fixture)
    {
        $dir = __DIR__.'/../../fixtures';
        $file = "$dir/JsonStore.$fixture.data";
        return file_get_contents($file);
    }

    private function saveFixture($fixture, $data)
    {
        return true;
        $dir = __DIR__.'/../../fixtures';
        $file = "$dir/JsonStore.$fixture.data";
        $f = fopen($file,'w');
        fwrite($f,$data);
        fclose($f);
    }

}
