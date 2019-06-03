<?php

use Sapaso\Helper\Pagination\DoctrinePaginator;
use Sapaso\Helper\Pagination\LinksMeta;
use Sapaso\Helper\Pagination\PagesMeta;

$container = $app->getContainer();

// for unittests, to prevent miltiple function definition
if (!function_exists('initializeCloudWatchLoggerHandler')) {
    function initializeCloudWatchLoggerHandler($awsSettings, $logPrefix, $logLevel, array $tags = [])
    {
        $cwClient = new \Aws\CloudWatchLogs\CloudWatchLogsClient($awsSettings['connection']);

        return new \Maxbanton\Cwh\Handler\CloudWatch(
            $cwClient,
            $awsSettings['cloudWatchLogGroup'],
            $logPrefix . '_' . date('Y-m-d'),
            $awsSettings['cloudWatchRetentionDays'],
            100,
            $tags,
            $logLevel
        );
    }
}

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// internal app monolog logger
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);

    // cloud watch for production and log file for other environments
    if (in_array(getenv('ENV'), ['production', 'sandbox', 'test'])) {
        $awsSettings = $c->get('settings')['aws'];
        $cwHandlerInstanceGeneral = initializeCloudWatchLoggerHandler(
            $awsSettings,
            $awsSettings['cloudWatchGeneralLogPrefix'],
            $settings['level']
        );
        $logger->pushHandler($cwHandlerInstanceGeneral);
    } else {
        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    }

    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    return $logger;
};

// requests monolog logger
$container['requests_logger'] = function ($c) {
    $settings = $c->get('settings')['requests_logger'];
    $logger = new Monolog\Logger($settings['name']);
    if (in_array(getenv('ENV'), ['production', 'sandbox', 'test'])) {
        $awsSettings = $c->get('settings')['aws'];
        $cwRequestsHandlerInstance = initializeCloudWatchLoggerHandler(
            $awsSettings,
            $awsSettings['cloudWatchRequestsLogPrefix'],
            $settings['level']
        );
        $logger->pushHandler($cwRequestsHandlerInstance);
    } else {
        $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], \Monolog\Logger::INFO));
    }
    return $logger;
};


// Doctrine MySQL
$container['em'] = function ($c) {
    $settings = $c->get('settings');
    $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        $settings['doctrine']['meta']['entity_path'],
        $settings['doctrine']['meta']['auto_generate_proxies'],
        $settings['doctrine']['meta']['proxy_dir'],
        $settings['doctrine']['meta']['cache'],
        false
    );
    /**
     * Add extra doctrine functions, see details at:
     * https://github.com/beberlei/DoctrineExtensions/blob/master/config/mysql.yml
     */
    $config->addCustomStringFunction('GROUP_CONCAT', 'DoctrineExtensions\Query\Mysql\GroupConcat');
    $config->addCustomStringFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
    $config->addCustomStringFunction('str_to_date', 'DoctrineExtensions\Query\Mysql\StrToDate');
    $config->addCustomStringFunction('date', 'DoctrineExtensions\Query\Mysql\Date');
    $config->addCustomNumericFunction('FLOOR', \DoctrineExtensions\Query\Mysql\Floor::class);
    $config->addCustomDatetimeFunction('DATEDIFF', \DoctrineExtensions\Query\Mysql\DateDiff::class);
    $config->addCustomDatetimeFunction('NOW', \DoctrineExtensions\Query\Mysql\Now::class);

    return \Doctrine\ORM\EntityManager::create($settings['doctrine']['connection']['mysql'], $config);
};

$container['doctrine_paginator'] = function ($c) {
    $uri = $c->get('request')->getUri();
    return new DoctrinePaginator(new LinksMeta($uri), new PagesMeta());
};
