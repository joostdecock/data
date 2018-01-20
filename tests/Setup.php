<?php

namespace Freesewing\Data\Tests;

use Freesewing\Data\Tests\TestApp;

class Setup extends \PHPUnit\Framework\TestCase
{
    public function testSetup()
    {
        $this->app = new TestApp();
        $db = $this->app->getContainer()->get('db');
        
        $sql = file_get_contents(__DIR__.'/sql/teardown.sql'); 
        $db->query($sql);
        $sql = file_get_contents(__DIR__.'/sql/setup.sql'); 
        $db->query($sql);
        $db = null;
    }
}
