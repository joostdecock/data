<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// database
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['database'],
        $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $pdo;
};

$container['InfoController'] = function ($container) {
    return new \App\Controllers\InfoController($container);
};

$container['UserController'] = function ($container) {
    return new \App\Controllers\UserController($container);
};

$container['ModelController'] = function ($container) {
    return new \App\Controllers\ModelController($container);
};

$container['DraftController'] = function ($container) {
    return new \App\Controllers\DraftController($container);
};

$container['ReferralController'] = function ($container) {
    return new \App\Controllers\ReferralController($container);
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

$container['User'] = function ($container) {
    return new \App\Data\User($container);
};

$container['Model'] = function ($container) {
    return new \App\Data\Model($container);
};

$container['Draft'] = function ($container) {
    return new \App\Data\Draft($container);
};

$container['Referral'] = function ($container) {
    return new \App\Data\Referral($container);
};
