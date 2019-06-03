<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once '../vendor/autoload.php';

$settings = require_once '../src/settings.php';
$settings = $settings['settings']['doctrine'];
$settings['options'] = [\PDO::ATTR_PERSISTENT => true];

$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
    $settings['meta']['entity_path'],
    $settings['meta']['auto_generate_proxies'],
    $settings['meta']['proxy_dir'],
    $settings['meta']['cache'],
    false
);

$em = \Doctrine\ORM\EntityManager::create($settings['connection']['mysql'], $config);

return ConsoleRunner::createHelperSet($em);
