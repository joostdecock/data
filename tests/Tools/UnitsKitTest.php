<?php

namespace Freesewing\Data\Tests\Tools;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Tools\UnitsKit;

class UnitsKitTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testAsFloat()
    {
        $obj = new UnitsKit($this->app->getContainer());

        $this->assertEquals($obj->asFloat(5.2), 5.2); 
        $this->assertEquals(floor($obj->asFloat('2 1/4')*100), 225); 
    }
}
