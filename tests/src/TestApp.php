<?php
namespace App\Tests;


class TestApp extends \Slim\App 
{
    public function __construct()
    {

        // Instantiate the app
        $settings = require __DIR__ . '/../../src/settings.php';
        // Overwrite storage path for testing
        $settings['settings']['storage'] = $settings['settings']['teststorage'];

        parent::__construct($settings);

        $container = $this->getContainer();

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

        return $this;
    }
}

