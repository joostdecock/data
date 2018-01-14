<?php
// Application middleware
$jwt = new \Slim\Middleware\JwtAuthentication($settings['settings']['jwt']);
$app->add($jwt);
