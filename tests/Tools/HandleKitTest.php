<?php

namespace Freesewing\Data\Tests\Tools;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Tools\HandleKit;

class HandleKitTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testCreateInvalidType()
    {
        $obj = new HandleKit($this->app->getContainer());
        $handle = $obj->create('invalid');

        $this->assertFalse($handle);
    }

    public function testCreate()
    {
        $obj = new HandleKit($this->app->getContainer());
        $handle = $obj->create('model');

        $this->assertTrue(is_string($handle));
        $this->assertEquals(strlen($handle), 5);
    }

}
