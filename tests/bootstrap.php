<?php
define('IS_TEST', true);
require __DIR__ . '/../vendor/autoload.php';

$app = new \App\Tests\TestApp();

// Prep database
$db = $app->getContainer()->get('db');
$db->exec(file_get_contents(__DIR__.'/sql/teardown.sql'));
$db->exec(file_get_contents(__DIR__.'/sql/setup.sql'));
