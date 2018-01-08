<?php

namespace App\Tests;

use \Slim\Container;

class UserTest extends \PHPUnit\Framework\TestCase
{

    private function bootstrap()
    {

        // Instantiate the app
        $settings = require __DIR__ . '/../../../src/settings.php';
        // Overwrite storage path for testing
        $settings['settings']['storage'] = $settings['settings']['teststorage'];

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

        // monolog
        $container['logger'] = function ($c) {
            $settings = $c->get('settings')['testlogger'];
            $logger = new \Monolog\Logger($settings['name']);
            $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        };
        
        $container['HandleKit'] = function ($container) {
            return new \App\Tools\HandleKit($container);
        };

        $container['AvatarKit'] = function ($container) {
            return new \App\Tools\AvatarKit($container);
        };
        
        $container['MigrationKit'] = function ($container) {
            return new \App\Tools\MigrationKit($container);
        };
        
        $container['MailKit'] = function ($container) {
            return new \App\Tools\MailKit($container);
        };
        
        $container['TokenKit'] = function ($container) {
            return new \App\Tools\TokenKit($container);
        };
        
        $container['UnitsKit'] = function ($container) {
            return new \App\Tools\UnitsKit($container);
        };

        return $app;
    }


    /** 
     * Tests constructor
     */
    public function testConstructor()
    {
        $app = $this->bootstrap();

        $obj = new \App\Data\User($app->getContainer());
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
        $app = $this->bootstrap();

        $obj = new \App\Data\User($app->getContainer());
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
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
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
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $obj->setPendingEmail('test@freesewing.org');
        $this->assertEquals($obj->getPendingEmail(),'test@freesewing.org');

        $obj->unsetPendingEmail();
        $this->assertFalse($obj->getPendingEmail());
    }
    
    public function testIsPatron()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $obj->setPatronTier(8);
        $this->assertTrue($obj->isPatron());

        $obj->setPatronTier(1);
        $this->assertFalse($obj->isPatron());
    }
    
    public function testCreate()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testCreate@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->assertEquals((string)$obj->getData(), '{}');
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testLoadFromId()
    {
        $app = $this->bootstrap();
        $obj1 = new \App\Data\User($app->getContainer());
        
        $email = time().'.testLoadFromId@freesewing.org';
        $obj1->create($email, 'boobies');
        $id = $obj1->getId();
        unset($obj1);

        $obj = new \App\Data\User($app->getContainer());
        $obj->loadFromId($id);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->assertEquals((string)$obj->getData(), '{}');
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testLoadFromHandle()
    {
        $app = $this->bootstrap();
        $obj1 = new \App\Data\User($app->getContainer());
        
        $email = time().'.testLoadFromHandle@freesewing.org';
        $obj1->create($email, 'boobies');
        $handle = $obj1->getHandle();
        unset($obj1);

        $obj = new \App\Data\User($app->getContainer());
        $obj->loadFromHandle($handle);
        $this->assertEquals($obj->getStatus(), 'inactive');
        $this->assertEquals($obj->getRole(), 'user');
        $this->assertEquals((string)$obj->getData(), '{}');
        $this->assertEquals($obj->getEmail(), $email);
    }
    
    public function testEmailTaken()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testEmailTaken@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertTrue($obj->emailTaken($email));
    }
    
    public function testUsernameTaken()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testUsernameTaken@freesewing.org';
        $obj->create($email, 'boobies');

        $obj->setUsername($email);
        $obj->save();

        // Username field is limimted to 32 chars 
        $this->assertTrue($obj->usernameTaken(substr($email,0,32)));
    }
    
    public function testGetActivationToken()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
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
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
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
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testCheckPassword@freesewing.org';
        $obj->create($email, 'boobies');

        $this->assertTrue($obj->checkPassword('boobies'));
        $this->assertFalse($obj->checkPassword('bewbies'));
    }

    public function testRemove()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testRemove@freesewing.org';
        $obj->create($email, 'boobies');

        $id = $obj->getId();
        $obj->remove();

        $this->assertFalse($obj->loadFromId($id));
    }

    public function testAddRemoveBadge()
    {
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
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
        $app = $this->bootstrap();
        $obj = new \App\Data\User($app->getContainer());
        
        $email = time().'.testMakePatron@freesewing.org';
        $obj->create($email, 'boobies');

        $obj->makePatron(8);

        $this->assertEquals($obj->getPatronTier(), 8);
        $this->assertTrue(is_int($obj->getPatronSince()));
    }
}
