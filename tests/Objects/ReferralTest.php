<?php

namespace Freesewing\Data\Tests\Data;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\Referral;

class ReferralTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    public function testCreate()
    {
        $obj = new Referral($this->app->getContainer());

        $host = 'yadayada.org';
        $path = '/foo/bar';
        $url = "https://$host/$path";

        $id = $obj->create($host, $path, $url);
        $obj->load($id);
        $obj->setSite('Other');
        $now = time(); 
        $obj->save($id);
        
        $this->assertEquals($obj->getHost(), $host);
        $this->assertEquals($obj->getPath(), $path);
        $this->assertEquals($obj->getUrl(), $url);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals($obj->getSite(), 'Other');
        $created = strtotime($obj->getTime());
        $this->assertTrue(abs($now-$created)<5);
    }
    
    public function testGroup()
    {
        $obj = new Referral($this->app->getContainer());

        $host = 'freesewing.org';
        $path = '/foo/bar';
        $url = "https://$host/$path";

        $id = $obj->create($host, $path, $url);
        $obj->load($id);
        $obj->group();
        
        $this->assertEquals($obj->getSite(), 'Freesewing');
    }
    
}
