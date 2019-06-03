<?php
namespace App\Action;

use Sapaso\Action\AbstractAction;
use Sapaso\Helper\Pagination\DoctrinePaginator;
use Sapaso\Resource\BookingResource;
use Sapaso\Service\ReturnStatus;
use Sapaso\Service\Serialiser;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class BookingAction extends AbstractAction
{
    public function __construct(Container $container, ?string $uri = null)
    {
        parent::__construct($container, $uri);
        $this->resource = new BookingResource($this->entityManager);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getPaginated(Request $request, Response $response, array $args = [])
    {
        $query = $this->resource->getSepaEntriesWithBookingsQuery((int)$args['customer_id']);

        $queryParams = $request->getParams();
        $page =      !empty($_ = $queryParams['page']) ? $_ : 1;
        $page_size = !empty($_ = $queryParams['size']) ? $_ : 10;

        /* @var $paginator DoctrinePaginator */
        $paginator = $this->container->get('doctrine_paginator');
        $bookings = $paginator->chunk($query, $page_size, $page);
        $body = $paginator->decorateResponseBody(ReturnStatus::returnSuccessWithPayload($bookings));

        return Serialiser::returnSerialised($body, ['list']);
    }
}
