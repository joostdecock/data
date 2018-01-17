<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Comment;
use Freesewing\Data\Objects\Error;

class ErrorTest extends \PHPUnit\Framework\TestCase
{

    private $errorData = [
        'level' => 64,
        'type' => 'php-error',
        'message' => 'Unknown error',
        'file' => 'index.php',
        'line' => '308',
        'origin' => 'data.freesewing.org',
        'user' => 'jrdoe',
        'ip' => '127.0.0.1',
        'status' => 'closed',
        'raw' => 'raw data'
    ];

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
        $this->assertClassHasAttribute($attribute, '\Freesewing\Data\Objects\Error');
    }

    public function providerTestAttributeExists()
    {
        return [
            ['level'],
            ['type'],
            ['message'],
            ['file'],
            ['line'],
            ['origin'],
            ['user'],
            ['ip'],
            ['time'],
            ['status'],
            ['hash'],
            ['raw']
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
        $obj = new Error($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['level', 64],
            ['type', 'php-error'],
            ['message', 'Unknown error'],
            ['file', 'index.php'],
            ['line', '308'],
            ['origin', 'data.freesewing.org'],
            ['user', 'jrdoe'],
            ['ip', '127.0.0.1'],
            ['status', 'closed'],
            ['hash', 'foobar'],
            ['raw', 'raw data']
        ];
    }
    
    public function testCreate()
    {
        $obj = new Error($this->app->getContainer());

        $obj->setLevel($this->errorData['level']);
        $obj->setType($this->errorData['type']);
        $obj->setMessage($this->errorData['message']);
        $obj->setFile($this->errorData['file']);
        $obj->setLine($this->errorData['line']);
        $obj->setOrigin($this->errorData['origin']);
        $obj->setUser($this->errorData['user']);
        $obj->setIp($this->errorData['ip']);
        $obj->setStatus($this->errorData['status']);
        $obj->hash();
        $obj->setRaw($this->errorData['raw']);
        $id = $obj->create();

        $obj->load($id);

        $this->assertEquals($obj->getId(),$id);
        $this->assertEquals($obj->getLevel(),$this->errorData['level']);
        $this->assertEquals($obj->getType(),$this->errorData['type']);
        $this->assertEquals($obj->getMessage(),$this->errorData['message']);
        $this->assertEquals($obj->getFile(),$this->errorData['file']);
        $this->assertEquals($obj->getLine(),$this->errorData['line']);
        $this->assertEquals($obj->getOrigin(),$this->errorData['origin']);
        $this->assertEquals($obj->getUser(),$this->errorData['user']);
        $this->assertEquals($obj->getIp(),$this->errorData['ip']);
        $this->assertEquals($obj->getStatus(),'new');
        $this->assertEquals($obj->getHash(),sha1($this->errorData['message']));
        $this->assertEquals($obj->getRaw(),$this->errorData['raw']);
        $this->assertTrue(is_string($obj->getTime()));

        $obj->mute();
        $this->assertEquals($obj->getStatus(),'muted');
        $obj->open();
        $this->assertEquals($obj->getStatus(),'open');
        $obj->close();
        $this->assertEquals($obj->getStatus(),'closed');
    }
    
    public function testSave()
    {
        $obj = new Error($this->app->getContainer());

        $id = $obj->create();
        $obj->load($id);
        
        $obj->setLevel($this->errorData['level']);
        $obj->setType($this->errorData['type']);
        $obj->setMessage($this->errorData['message']);
        $obj->setFile($this->errorData['file']);
        $obj->setLine($this->errorData['line']);
        $obj->setOrigin($this->errorData['origin']);
        $obj->setUser($this->errorData['user']);
        $obj->setIp($this->errorData['ip']);
        $obj->setStatus($this->errorData['status']);
        $obj->hash();
        $obj->setRaw($this->errorData['raw']);
        $obj->save();

        $this->assertEquals($obj->getId(),$id);
        $this->assertEquals($obj->getLevel(),$this->errorData['level']);
        $this->assertEquals($obj->getType(),$this->errorData['type']);
        $this->assertEquals($obj->getMessage(),$this->errorData['message']);
        $this->assertEquals($obj->getFile(),$this->errorData['file']);
        $this->assertEquals($obj->getLine(),$this->errorData['line']);
        $this->assertEquals($obj->getOrigin(),$this->errorData['origin']);
        $this->assertEquals($obj->getUser(),$this->errorData['user']);
        $this->assertEquals($obj->getIp(),$this->errorData['ip']);
        $this->assertEquals($obj->getStatus(),$this->errorData['status']);
        $this->assertEquals($obj->getHash(),sha1($this->errorData['message']));
        $this->assertEquals($obj->getRaw(),$this->errorData['raw']);
        $this->assertTrue(is_string($obj->getTime()));
    
        $obj->remove();
        $this->assertFalse($obj->load($id));
    }
}
