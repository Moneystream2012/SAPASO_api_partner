<?php

use App\Action\BookingAction;
use App\Action\CustomerAction;
use App\Action\DocketAction;
use App\Action\DocketLineAction;

$app->group('/customer/{customer_id}', function () use ($app) {

    $app->get('/bookings', BookingAction::class . ':getPaginated');

    $app->get('', CustomerAction::class . ':getOne');

    $app->get('/dockets/summary', DocketAction::class . ':summary');

    $app->get('/dockets', DocketAction::class . ':getPaginated');

    $app->get('/dockets/{docket_id}', DocketAction::class . ':getOne');

    /**
     * Add DockerLine
     * Sample JSON:
     * {
     *   "amount" : 0, (integer) [in euro-cents]
     *   "sepa_entry_id" : 1, (integer)
     *   "type" : "MANUAL/ RETURN/ RETURN_CHARGE/ INITIAL/ COST", (string)
     *   "description" : "", (string)
     * }
     */
    $app->post('/dockets/{docket_id}/docketLine', DocketLineAction::class . ':addDocketLine');

    /**
     * Any of this routes
     *    /dockets/{docket_id}
     *    /dockets/{docket_id}/status/dunning
     *    /dockets/{docket_id}/status/DUNNING
     * will cause send Docket to COEO & became Docket to STATUS_DUNNING
     */
    $app->post('/dockets/{docket_id}', DocketAction::class . ':proceedDocket');

    $app->post('/dockets/{docket_id}/status/{proceed}', DocketAction::class . ':proceedDocket');
})->add(new \App\Middleware\HasPermissionsToReadCustomer($container, 'customer_id'));
