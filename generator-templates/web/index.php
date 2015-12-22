<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
// Uncomment the following lines for local Radvance development
// $pathToRadvance = __DIR__ .'/../../src/';
// $loader->addPsr4('Radvance\\', $pathToRadvance, true);

$app = require_once __DIR__.'/../app/bootstrap.php';
$app->run();
