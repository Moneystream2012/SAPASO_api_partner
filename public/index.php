<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
$loader = require __DIR__.'/../vendor/autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

require_once __DIR__.'/../vendor/autoload.php';

// Run app
$app = (new \App\App())->get();
$app->run();
