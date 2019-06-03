<?php

/**
 * AUTHORISED PATHS, oAuth is needed to access this
 * @FIXME TEST CREDENTIAL IS:
 *   INSERT INTO `sapaso`.`Partners` (`email`, `password`) VALUES ('akas@livatek.com', '$2y$10$ZHT5nQTTvg5vAOhr0.DE7uF.q0L1U/v66J3qt7vfK6B1GhS1MkH8u');
 */
$app->group($settings['settings']['baseUri'], function () use ($app, $container, $authorization) {

    $app->add(new \App\Middleware\PartnerByAccessToken($container));

    // Add the customer routing
    require __DIR__ . '/Routes/customer.php';
})->add($authorization);

/**
 * UNAUTHORISED PATHS, accessible to all
 */
$app->group($settings['settings']['baseUri'], function ($app) use ($container) {

    /**
     * Return OK/200 for the AWS ECS signalling the docker is alive
     */
    $app->get('/health', function ($request, $response, $args) use ($app) {
        return \Sapaso\Service\Serialiser::returnSerialised(\Sapaso\Helper\HealthStatus::returnStatusOK($app->getContainer()));
    });
});
