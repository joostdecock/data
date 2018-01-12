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

        $host = 'test.freesewing.org';
        $path = '/foo/bar';
        $url = "https://$host/$path";

        $now = date('Y-m-d H:i');
        $id = $obj->create($host, $path, $url);
        $obj->load($id);
        $obj->setSite('Other');
        $obj->save($id);
        
        $this->assertEquals($obj->getHost(), $host);
        $this->assertEquals($obj->getPath(), $path);
        $this->assertEquals($obj->getUrl(), $url);
        $this->assertEquals($obj->getId(), $id);
        $this->assertEquals(substr($obj->getTime(), 0, 16), $now);
        $this->assertEquals($obj->getSite(), 'Other');
    }
    
    public function testGroup()
    {
        $obj = new Referral($this->app->getContainer());

        $host = 'test.freesewing.org';
        $path = '/foo/bar';
        $url = "https://$host/$path";

        $now = date('Y-m-d H:i');
        $id = $obj->create($host, $path, $url);
        $obj->load($id);
        $obj->group();
        
        $this->assertEquals($obj->getSite(), 'Freesewing');
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
        $this->assertEquals($obj->getMigrated(), 0);
        $this->assertEquals($obj->getShared(), 0);
    }
    
    public function testLoadFromHandle()
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
        $this->assertEquals($obj->getHandle(), $handle);
        $this->assertEquals($obj->getName(), '#'.$obj->getId());
        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getUnits(), $user->getAccountUnits());
        $this->assertEquals($obj->getMigrated(), 0);
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
}
