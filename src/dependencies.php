<?php
if(is_a($app, '\Freesewing\Data\Tests\TestFreesewing\Data')) if(!defined('IS_TEST')) define('IS_TEST', true);

// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    if(defined('IS_TEST')) $settings = $c->get('settings')['testlogger'];
    else $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// database
$container['db'] = function ($c) {
    if(defined('IS_TEST')) $db = $c['settings']['testdb'];
    else $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['database'],
        $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $pdo;
};

// Mailgun
$container['Mailgun'] = function ($c) {
    return \Mailgun\Mailgun::create($c['settings']['mailgun']['api_key']);
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

// Guzzle
$container['GuzzleClient'] = function () { 
    return new \GuzzleHttp\Client(); 
};

// Imagick
$container['Imagick'] = function () { return new \Imagick(); };

// Own classes
$container['InfoController'] = function ($container) {
    return new \Freesewing\Data\Controllers\InfoController($container);
};

$container['UserController'] = function ($container) {
    return new \Freesewing\Data\Controllers\UserController($container);
};

$container['ModelController'] = function ($container) {
    return new \Freesewing\Data\Controllers\ModelController($container);
};

$container['DraftController'] = function ($container) {
    return new \Freesewing\Data\Controllers\DraftController($container);
};

$container['ReferralController'] = function ($container) {
    return new \Freesewing\Data\Controllers\ReferralController($container);
};

$container['CommentController'] = function ($container) {
    return new \Freesewing\Data\Controllers\CommentController($container);
};

$container['ToolsController'] = function ($container) {
    return new \Freesewing\Data\Controllers\ToolsController($container);
};

$container['HandleKit'] = function ($container) {
    return new \Freesewing\Data\Tools\HandleKit($container);
};

$container['AvatarKit'] = function ($container) {
    return new \Freesewing\Data\Tools\AvatarKit($container);
};

$container['MigrationKit'] = function ($container) {
    return new \Freesewing\Data\Tools\MigrationKit($container);
};

$container['MailKit'] = function ($container) {
    return new \Freesewing\Data\Tools\MailKit($container);
};

$container['TokenKit'] = function ($container) {
    return new \Freesewing\Data\Tools\TokenKit($container);
};

$container['UnitsKit'] = function ($container) {
    return new \Freesewing\Data\Tools\UnitsKit($container);
};

$container['User'] = function ($container) {
    return new \Freesewing\Data\Objects\User($container);
};

$container['Model'] = function ($container) {
    return new \Freesewing\Data\Objects\Model($container);
};

$container['Draft'] = function ($container) {
    return new \Freesewing\Data\Objects\Draft($container);
};

$container['Referral'] = function ($container) {
    return new \Freesewing\Data\Objects\Referral($container);
};

$container['Comment'] = function ($container) {
    return new \Freesewing\Data\Objects\Comment($container);
};

$container['JsonStore'] = function ($container) {
    return new \Freesewing\Data\Objects\JsonStore($container);
};
