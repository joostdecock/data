<?php

namespace Freesewing\Data\Tests\Data;

use Freesewing\Data\Tests\TestApp;

class UserTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        //$app = new TestApp();
        $obj = new \App\Data\User($this->app->getContainer());
        $json = new \App\Data\JsonStore();

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
        $obj = new \App\Data\User($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['Login', '2018-01-08 11:15:30'],
            ['Email', 'test@freesewing.org'],
            ['Status', 'active'],
            ['Migrated', '2018-01-07 10:30:15'],
            ['Role', 'user'],
            ['Picture', 'avatar.jpg'],
            ['PendingEmail', 'newaddress@freesewing.org'],
            ['AccountUnits', 'metric'],
            ['AccountTheme', 'classic'],
            ['TwitterHandle', 'freesewing_org'],
            ['InstagramHandle', 'same_here'],
            ['GithubHandle', 'joostdecock'],
            ['PatronTier', 8],
            ['PatronSince', '1515406642'],
            ['PatronAddress', "Mr. Harry Potter\nThe cupboard under the stairs\n4 Privet Drive\nLittle Whinging\nSurrey"],
        ];
    }

    public function testSetPatron()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $obj->setPatron(8, '1515406642');
        $this->assertEquals($obj->getPatronTier(),8);
        $this->assertEquals($obj->getPatronSince(),'1515406642');
        $address = "Mr. Harry Potter\nThe cupboard under the stairs\n4 Privet Drive\nLittle Whinging\nSurrey";
        $obj->setPatron(4, '1515406641',$address, 10, 12);
        $this->assertEquals($obj->getPatronTier(),4);
        $this->assertEquals($obj->getPatronSince(),'1515406641');
        $this->assertEquals($obj->getPatronAddress(),$address);
        $this->assertEquals($obj->getPatronBirthday(),'10/12');
    }

    public function testUnsetPendingEmail()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $obj->setPendingEmail('test@freesewing.org');
        $this->assertEquals($obj->getPendingEmail(),'test@freesewing.org');

        $obj->unsetPendingEmail();
        $this->assertFalse($obj->getPendingEmail());
    }
    
    public function testIsPatron()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $obj->setPatronTier(8);
        $this->assertTrue($obj->isPatron());

        $obj->setPatronTier(1);
        $this->assertFalse($obj->isPatron());
    }
    
    public function testCreate()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testCreate@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.create',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.create'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testLoadFromId()
    {
        $obj1 = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testLoadFromId@freesewing.org';
        $obj1->create($email, 'boobies');
        $id = $obj1->getId();
        unset($obj1);

        $obj = new \App\Data\User($this->app->getContainer());
        $obj->loadFromId($id);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.load.from.id',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.load.from.id'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testLoadFromHandle()
    {
        $obj1 = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testLoadFromHandle@freesewing.org';
        $obj1->create($email, 'boobies');
        $handle = $obj1->getHandle();
        unset($obj1);

        $obj = new \App\Data\User($this->app->getContainer());
        $obj->loadFromHandle($handle);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.load.from.handle',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.load.from.handle'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testEmailTaken()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testEmailTaken@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertTrue($obj->emailTaken($email));
    }
    
    public function testUsernameTaken()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testUsernameTaken@freesewing.org';
        $obj->create($email, 'boobies');

        $obj->setUsername($email);
        $obj->save();

        // Username field is limimted to 32 chars 
        $this->assertTrue($obj->usernameTaken(substr($email,0,32)));
    }
    
    public function testGetActivationToken()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testGetActivationToken@freesewing.org';
        $obj->create($email, 'boobies');

        $token1 = $obj->getActivationToken();
        $token2 = $obj->getActivationToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals(strlen($token1), 64);
        $this->assertEquals(strtolower($token1), $token2);
    }
    
    public function testGetResetToken()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testGetResetToken@freesewing.org';
        $obj->create($email, 'boobies');

        $token1 = $obj->getResetToken();
        $token2 = $obj->getResetToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals(strlen($token1), 64);
        $this->assertEquals(strtolower($token1), $token2);
    }
    
    public function testCheckPassword()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testCheckPassword@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertTrue($obj->checkPassword('boobies'));
        $this->assertFalse($obj->checkPassword('bewbies'));
    }

    public function testRemove()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testRemove@freesewing.org';
        $obj->create($email, 'boobies');

        $id = $obj->getId();
        $obj->remove();

        $this->assertFalse($obj->loadFromId($id));
    }

    public function testAddRemoveBadge()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testAddRemoveBadge@freesewing.org';
        $obj->create($email, 'boobies');

        $obj->addBadge('test');

        $badges = new \stdClass();
        $badges->test = true;
        $this->assertEquals($obj->getBadges(), $badges);
        
        $obj->removeBadge('test');
        $this->assertEquals($obj->getBadges(), new \stdClass());
    }

    public function testMakePatron()
    {
        $obj = new \App\Data\User($this->app->getContainer());
        
        $email = time().'.testMakePatron@freesewing.org';
        $obj->create($email, 'boobies');

        $obj->makePatron(8);

        $this->assertEquals($obj->getPatronTier(), 8);
        $this->assertTrue(is_int($obj->getPatronSince()));
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
