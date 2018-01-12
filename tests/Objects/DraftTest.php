<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\Draft;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\JsonStore;

class DraftTest extends \PHPUnit\Framework\TestCase
{
    protected function setup() {
        if(!isset($this->app)) $this->app = new TestApp();
    }

    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        $obj = new Draft($this->app->getContainer());
        $json = new JsonStore();

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
        $obj = new Draft($this->app->getContainer());
        $setMethod = 'set'.$methodSuffix;
        $getMethod = 'get'.$methodSuffix;
        $obj->{$setMethod}($expectedResult);
        $this->assertEquals($expectedResult, $obj->{$getMethod}());
    }

    public function providerGettersReturnWhatSettersSet()
    {
        return [
            ['User', 2],
            ['Model', 3],
            ['Pattern', 'TrayvonTie'],
            ['Handle', 'testy'],
            ['Name', 'Test draft'],
            ['Svg', '<svg></svg>'],
            ['Compared', '<svg class="compare"></svg>'],
            ['Notes', 'These are my notes'],
            ['Shared', 1],
            ['CoreUrl', 'some long url here'],
            ['Units', 'imperial'],
            ['Version', 'test1.0'],
        ];
    }

    public function testCreate()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraft@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals($obj->data->getNode('measurements.neckCircumference'), 42);
    }

    public function testCreateMixedUnitsImperial()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraftMixedUnitsImperial@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 20);
        $model->setMeasurement('neckCircumference', 16);
        $model->setUnits('imperial');

        // Draft data
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(floor($obj->data->getNode('measurements.neckCircumference')), 40);
    }
    
    public function testCreateMixedUnitsMetric()
    {
        $obj = new Draft($this->app->getContainer());
        $user = new User($this->app->getContainer());
        $model = new Model($this->app->getContainer());
        
        // We need a User object
        $email = time().'.testCreateDraftMixedUnitsMetric@freesewing.org';
        $user->create($email, 'bananas');
        
        // We need a Model object
        $model->create($user);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft data
        $data = [
            'userUnits' => 'imperial',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];

        $obj->create($data, $user,$model);

        $this->assertEquals($obj->getUser(), $user->getId());
        $this->assertEquals($obj->getPattern(), 'TrayvonTie');
        $this->assertEquals($obj->getModel(), $model->getId());
        $this->assertEquals($obj->getName(), 'Draft '.$obj->getHandle());
        $this->assertEquals($obj->getNotes(), $this->app->getContainer()['settings']['app']['motd']);
        $this->assertEquals($obj->data->getNode('options.theme'), 'Basic');
        $this->assertEquals(floor($obj->data->getNode('measurements.neckCircumference')), 16);
    }

    public function estSetInvalidStatusOrRole()
    {
        $obj = new User($this->app->getContainer());
        
        $this->assertFalse($obj->setStatus('demi-god'));
        $this->assertFalse($obj->setRole('demi-god'));
    }

    public function estGetSocial()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setTwitterHandle('freesewing_org');
        $obj->setInstagramHandle('joostdecock');
        $obj->setGithubHandle('freesewing');

        $check = [
            'twitter' => 'freesewing_org',
            'instagram' => 'joostdecock',
            'github' => 'freesewing'
        ];

        $this->assertEquals($obj->getSocial(), (object)$check);
    }

    public function estSetInvalidSocialHandle()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setTwitterHandle('@fs');
        $obj->setInstagramHandle('jd');
        $obj->setGithubHandle('fs');

        $this->assertFalse($obj->getSocial());
    }

    public function estSetPatron()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setPatron(8, '1515406642');
        $this->assertEquals($obj->getPatronTier(),8);
        $this->assertEquals($obj->getPatronSince(),'1515406642');
        $address = "Mr. Harry Potter\nThe cupboard under the stairs\n4 Privet Drive\nLittle Whinging\nSurrey";
        $obj->setPatron(4, '1515406641',$address, 10, 12);
        $this->assertEquals($obj->getPatronTier(),4);
        $this->assertEquals($obj->getPatronSince(),'1515406641');
        $this->assertEquals($obj->getPatronAddress(),$address);
        $this->assertEquals($obj->getPatronBirthday(),'10/12');

        $check = [
            'tier' => 4,
            'since' => '1515406641',
            'address' => $address,
            'birthday' => (object)['day' => 10, 'month' => 12]
        ];
        $this->assertEquals($obj->getPatron(), (object)$check);
    }

    public function estUnsetPendingEmail()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setPendingEmail('test@freesewing.org');
        $this->assertEquals($obj->getPendingEmail(),'test@freesewing.org');

        $obj->unsetPendingEmail();
        $this->assertFalse($obj->getPendingEmail());
    }
    
    public function estIsPatron()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setPatronTier(8);
        $this->assertTrue($obj->isPatron());

        $obj->setPatronTier(1);
        $this->assertFalse($obj->isPatron());
    }
    
    
    public function estLoadFromId()
    {
        $obj1 = new User($this->app->getContainer());
        
        $email = time().'.testLoadFromId@freesewing.org';
        $obj1->create($email, 'bananas');
        $id = $obj1->getId();
        unset($obj1);

        $obj = new User($this->app->getContainer());
        $obj->loadFromId($id);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.load.from.id',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.load.from.id'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function estLoadFromHandle()
    {
        $obj1 = new User($this->app->getContainer());
        
        $email = time().'.testLoadFromHandle@freesewing.org';
        $obj1->create($email, 'bananas');
        $handle = $obj1->getHandle();
        unset($obj1);

        $obj = new User($this->app->getContainer());
        $obj->loadFromHandle($handle);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.load.from.handle',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.load.from.handle'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function estEmailTaken()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testEmailTaken@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertTrue($obj->emailTaken($email));
        $this->assertFalse($obj->emailTaken(time().$email));
    }
    
    public function estUsernameTaken()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testUsernameTaken@freesewing.org';
        $obj->create($email, 'bananas');

        $obj->setUsername($email);
        $obj->save();

        // Username field is limimted to 32 chars 
        $this->assertTrue($obj->usernameTaken(substr($email,0,32)));
        $this->assertFalse($obj->usernameTaken(substr(time().$email,0,32)));
    }
    
    public function estGetInitial()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetInitial@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertEquals($obj->getInitial(), $email);
    }
    
    public function estGetPictureUrl()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetPictureUrl@freesewing.org';
        $obj->create($email, 'bananas');
        $handle = $obj->getHandle();

        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$obj->getPictureUrl());
        $this->assertTrue(file_exists($path));
    }
    
    public function estGetActivationToken()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetActivationToken@freesewing.org';
        $obj->create($email, 'bananas');

        $token1 = $obj->getActivationToken();
        $token2 = $obj->getActivationToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals(strlen($token1), 64);
        $this->assertEquals(strtolower($token1), $token2);
    }
    
    public function estGetResetToken()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetResetToken@freesewing.org';
        $obj->create($email, 'bananas');

        $token1 = $obj->getResetToken();
        $token2 = $obj->getResetToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals(strlen($token1), 64);
        $this->assertEquals(strtolower($token1), $token2);
    }
    
    public function estCheckPassword()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testCheckPassword@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertTrue($obj->checkPassword('bananas'));
        $this->assertFalse($obj->checkPassword('bewbies'));
    }

    public function estRemove()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testRemove@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->remove();

        $this->assertFalse($obj->loadFromEmail($email));
    }

    public function estAddRemoveBadge()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testAddRemoveBadge@freesewing.org';
        $obj->create($email, 'bananas');

        $obj->addBadge('test');

        $badges = new \stdClass();
        $badges->test = true;
        $this->assertEquals($obj->getBadges(), $badges);
        
        $obj->removeBadge('test');
        $this->assertEquals($obj->getBadges(), new \stdClass());
    }

    public function estMakePatron()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testMakePatron@freesewing.org';
        $obj->create($email, 'bananas');

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
