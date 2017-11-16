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

// swift mailer
$container['SwiftMailer'] = function ($c) {
    $settings = $c->settings['swiftmailer'];
    $transport = (new \Swift_SmtpTransport())
        ->setHost($settings['host'])
        ->setPort($settings['port'])
        ->setEncryption($settings['encryption'])
        ->setUsername($settings['username'])
        ->setPassword($settings['password'])
    ;
    return new \Swift_Mailer($transport);
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

$container['CommentController'] = function ($container) {
    return new \App\Controllers\CommentController($container);
};

$container['ToolsController'] = function ($container) {
    return new \App\Controllers\ToolsController($container);
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

$container['Comment'] = function ($container) {
    return new \App\Data\Comment($container);
};
