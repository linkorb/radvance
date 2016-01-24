<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
\AutoTune\Tuner::init($loader);

$app = require_once __DIR__.'/../app/bootstrap.php';
$app->run();
