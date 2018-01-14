<?php

namespace Freesewing\Data\Tests\Objects;

use Freesewing\Data\Tests\TestApp;
use Freesewing\Data\Objects\User;
use Freesewing\Data\Objects\Draft;
use Freesewing\Data\Objects\Model;
use Freesewing\Data\Objects\Comment;
use Freesewing\Data\Objects\JsonStore;

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
        $obj = new User($this->app->getContainer());
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
        $obj = new User($this->app->getContainer());
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

    public function testSetInvalidStatusOrRole()
    {
        $obj = new User($this->app->getContainer());
        
        $this->assertFalse($obj->setStatus('demi-god'));
        $this->assertFalse($obj->setRole('demi-god'));
    }

    public function testGetSocial()
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

    public function testSetInvalidSocialHandle()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setTwitterHandle('@fs');
        $obj->setInstagramHandle('jd');
        $obj->setGithubHandle('fs');

        $this->assertFalse($obj->getSocial());
    }

    public function testSetPatron()
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

    public function testUnsetPendingEmail()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setPendingEmail('test@freesewing.org');
        $this->assertEquals($obj->getPendingEmail(),'test@freesewing.org');

        $obj->unsetPendingEmail();
        $this->assertFalse($obj->getPendingEmail());
    }
    
    public function testIsPatron()
    {
        $obj = new User($this->app->getContainer());
        
        $obj->setPatronTier(8);
        $this->assertTrue($obj->isPatron());

        $obj->setPatronTier(1);
        $this->assertFalse($obj->isPatron());
    }
    
    public function testCreate()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testCreate@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->saveFixture('user.json.create',$obj->getDataAsJson());
        $this->assertEquals($obj->getDataAsJson(), $this->loadFixture('user.json.create'));
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testLoadFromId()
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
    
    public function testLoadFromHandle()
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
    
    public function testEmailTaken()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testEmailTaken@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertTrue($obj->emailTaken($email));
        $this->assertFalse($obj->emailTaken(time().$email));
    }
    
    public function testUsernameTaken()
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
    
    public function testGetInitial()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetInitial@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertEquals($obj->getInitial(), $email);
    }
    
    public function testGetPictureUrl()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testGetPictureUrl@freesewing.org';
        $obj->create($email, 'bananas');
        $handle = $obj->getHandle();

        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$obj->getPictureUrl());
        $this->assertTrue(file_exists($path));
    }
    
    public function testGetActivationToken()
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
    
    public function testGetResetToken()
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
    
    public function testCheckPassword()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testCheckPassword@freesewing.org';
        $obj->create($email, 'bananas');

        $this->assertTrue($obj->checkPassword('bananas'));
        $this->assertFalse($obj->checkPassword('bewbies'));
    }

    public function testRemove()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testRemove@freesewing.org';
        $obj->create($email, 'bananas');
        $obj->remove();

        $this->assertFalse($obj->loadFromEmail($email));
    }

    public function testAddRemoveBadge()
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

    public function testMakePatron()
    {
        $obj = new User($this->app->getContainer());
        
        $email = time().'.testMakePatron@freesewing.org';
        $obj->create($email, 'bananas');

        $obj->makePatron(8);

        $this->assertEquals($obj->getPatronTier(), 8);
        $this->assertTrue(is_int($obj->getPatronSince()));
    }
    
    public function testSetData()
    {
        $data = new JsonStore($this->app->GetContainer()); 
        $data->setNode('measurements.test1','value1');
        $data->setNode('measurements.test2','value2');
        $data->setNode('options.test1','value1');
        $data->setNode('options.test2','value2');
        
        $obj = new User($this->app->getContainer());
        $obj->setData($data);

        $this->assertEquals($obj->getData(), $data);
    }
    
    public function testGetModels()
    {
        $obj = new User($this->app->getContainer());
        $email = time().'.testGetModels@freesewing.org';
        $obj->create($email, 'bananas');

        $model1 = new Model($this->app->getContainer());
        $model2 = new Model($this->app->getContainer());
        
        $model1->create($obj);
        $model2->create($obj);
        $model1->setUnits('imperial');
        $model2->setMeasurement('headCircumference', 50);
        $model1->save();
        $model2->save();

        $models = $obj->getModels();
        $m1 = $models[$model1->getHandle()];
        $m2 = $models[$model2->getHandle()];
        $this->assertTrue(is_object($m1));
        $this->assertTrue(is_object($m2));
        $this->assertEquals($m1->units, 'imperial');
        $this->assertEquals($m2->data->measurements->headCircumference, 50);
    }
    
    public function testGetComments()
    {
        $obj = new User($this->app->getContainer());
        $email = time().'.testGetComments@freesewing.org';
        $obj->create($email, 'bananas');

        $comment1 = new Comment($this->app->getContainer());
        $comment1->setComment("This is a test comment");
        $comment1->setPage('/unit/test');
        $comment1->create($obj);

        $comment2 = new Comment($this->app->getContainer());
        $comment2->setComment("This is a reply comment");
        $comment2->setPage('/unit/test');
        $comment2->setParent($comment1->getId());
        $comment2->create($obj);

        $comment3 = new Comment($this->app->getContainer());
        $comment3->setComment("This is a reply comment to a reply comment");
        $comment3->setPage('/unit/test');
        $comment3->setParent($comment1->getId());
        $comment3->create($obj);

        $comments = $obj->getComments();
        
        $c1 = $comments[$comment1->getId()];
        $c2 = $comments[$comment2->getId()];
        $c3 = $comments[$comment3->getId()];
        $this->assertTrue(is_object($c1));
        $this->assertTrue(is_object($c2));
        $this->assertTrue(is_object($c3));
        $this->assertEquals($c1->page, '/unit/test');
        $this->assertEquals($c2->parent, $comment1->getId());
        $this->assertEquals($c3->comment, "This is a reply comment to a reply comment");
    }
    
    public function testGetDrafts()
    {
        $obj = new User($this->app->getContainer());
        $email = time().'.testGetDrafts@freesewing.org';
        $obj->create($email, 'bananas');
        
        // We need a Model object
        $model = new Model($this->app->getContainer());
        $model->create($obj);
        $model->setMeasurement('centerbackneckToWaist', 52);
        $model->setMeasurement('neckCircumference', 42);
        $model->setUnits('metric');

        // Draft 1
        $draft1 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];
        $draft1->create($data, $obj,$model);
        
        // Draft 2
        $draft2 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Paperless',
            'pattern' => 'TrayvonTie',
        ];
        $draft2->create($data, $obj,$model);
        
        $drafts = $obj->getDrafts();
        $d1 = $drafts[$draft1->getId()];
        $d2 = $drafts[$draft2->getId()];

        $this->assertTrue(is_object($d1));
        $this->assertTrue(is_object($d2));
        $this->assertEquals($d1->pattern, 'TrayvonTie');
        $this->assertEquals($d2->id, $draft2->getId());
    }
    
    public function testExport()
    {
        $obj = new User($this->app->getContainer());
        $email = time().'.testExport@freesewing.org';
        $obj->create($email, 'bananas');

        $model1 = new Model($this->app->getContainer());
        $model2 = new Model($this->app->getContainer());
        
        $model1->create($obj);
        $model1->setMeasurement('centerbackneckToWaist', 52);
        $model1->setMeasurement('neckCircumference', 42);
        $model1->setUnits('metric');
        $model1->save();
        
        $model2->create($obj);
        $model2->setMeasurement('centerbackneckToWaist', 52);
        $model2->setMeasurement('neckCircumference', 42);
        $model2->setUnits('metric');
        $model2->save();
        
        $comment1 = new Comment($this->app->getContainer());
        $comment1->setComment("This is a test comment");
        $comment1->setPage('/unit/test');
        $comment1->create($obj);

        $comment2 = new Comment($this->app->getContainer());
        $comment2->setComment("This is a reply comment");
        $comment2->setPage('/unit/test');
        $comment2->setParent($comment1->getId());
        $comment2->create($obj);

        $comment3 = new Comment($this->app->getContainer());
        $comment3->setComment("This is a reply comment to a reply comment");
        $comment3->setPage('/unit/test');
        $comment3->setParent($comment1->getId());
        $comment3->create($obj);

        // Draft 1
        $draft1 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Basic',
            'pattern' => 'TrayvonTie',
        ];
        $draft1->create($data, $obj,$model1);
        
        // Draft 2
        $draft2 = new Draft($this->app->getContainer());
        $data = [
            'userUnits' => 'metric',
            'theme' => 'Paperless',
            'pattern' => 'TrayvonTie',
        ];
        $draft2->create($data, $obj,$model2);

        $zip = $obj->export();
        $path = $this->app->getContainer()['settings']['storage']['static_path'].str_replace('/static','',$zip);
        $this->assertTrue(file_exists($path));
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
