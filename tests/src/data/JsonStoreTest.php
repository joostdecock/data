<?php

namespace App\Tests;

use \Slim\Container;

class JsonStoreTest extends \PHPUnit\Framework\TestCase
{

    private function bootstrap()
    {

        // Instantiate the app
        $settings = require __DIR__ . '/../../../src/settings.php';
        $app = new \Slim\App($settings);
        $container = $app->getContainer();

        // database
        $container['db'] = function ($c) {
            $db = $c['settings']['testdb'];
            $pdo = new \PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['database'],
            $db['user'], $db['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            return $pdo;
        };

        return $app;
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
