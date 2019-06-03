<?php
namespace Sapaso;

use Sapaso\Action\Status\NotFoundHandler;
use Sapaso\Action\Status\NotAllowedHandler;
use Sapaso\Action\Status\ErrorHandler;
use Sapaso\Action\Status\PhpErrorHandler;

$c = $app->getContainer();

/**
 * Custom 500 Error - Something went wrong
 */
$c['errorHandler'] = function ($c) { 
    return new ErrorHandler(function ($request, $response) use ($c) {
        return $c['response']; 
    }); 
};

/**
 * Custom 404 Error - Requested Endpoint was not found
 */
$c['notFoundHandler'] = function ($c) { 
    return new NotFoundHandler(function ($request, $response) use ($c) {
        return $c['response']; 
    }); 
};

/**
 * Custom 405 Error - Requested Endpoint was not found
 */
$c['notAllowedHandler'] = function ($c) { 
    return new NotAllowedHandler(function ($request, $response) use ($c) {
        return $c['response']; 
    }); 
};

$c['phpErrorHandler'] = function ($c) {
    return new PhpErrorHandler(function ($request, $response, $error) use ($c) {
        return $c['response'];
    });
};