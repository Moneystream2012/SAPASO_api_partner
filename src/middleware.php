<?php

use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware;
use Sapaso\Entity\Partner;
use Slim\Views;
use OAuth2\GrantType;
use Sapaso\Service\OauthStorage;

/**
 * Log request uri and username
 */
$app->add(new \App\Middleware\RequestsLogger($app->getContainer()));

// Connect to the database via PDO to access the oAuth store
$pdoSettings = $settings['settings']['doctrine']['connection']['mysql'];
$pdo = new \PDO('mysql:host='.$pdoSettings['host'].';dbname='.$pdoSettings['dbname'], $pdoSettings['user'], $pdoSettings['password']);
$config = [
    'client_table' => 'Partners',
    'scope_table' => 'AccessScopes',
    'access_token_table' => 'PartnerAccessTokens'
];

$storage = new OauthStorage($pdo, $config);
$server = new OAuth2\Server(
    $storage,
    [
        'access_lifetime' => Partner::ACCESS_TOKEN_LIFETIME_HOURS * 60 * 60,
    ],
    [
        new GrantType\ClientCredentials($storage),
        new GrantType\AuthorizationCode($storage),
    ]
);

//$renderer = new Views\PhpRenderer( __DIR__ . '/vendor/chadicus/slim-oauth2-routes/templates');

$prefix = $settings['settings']['baseUri'];
//$app->map(['GET', 'POST'], $prefix.Routes\Authorize::ROUTE, new Routes\Authorize($server, $renderer))->setName('authorize');
$app->post($prefix.Routes\Token::ROUTE, new Routes\Token($server))->setName('token');
//$app->map(['GET', 'POST'], $prefix.Routes\ReceiveCode::ROUTE, new Routes\ReceiveCode($renderer))->setName('receive-code');

$authorization = new Middleware\Authorization($server, $app->getContainer());
