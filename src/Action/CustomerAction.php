<?php
namespace App\Action;

use App\Representation\Customer as CustomerRepresentation;
use Sapaso\Action\AbstractAction;
use Sapaso\Entity\ClientCustomer;
use Sapaso\Resource\ClientCustomerResource;
use Sapaso\Service\ReturnStatus;
use Sapaso\Service\Serialiser;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CustomerAction extends AbstractAction
{
    public function __construct(Container $container, ?string $uri = null)
    {
        $this->entityManager = $container->get('em');
        $this->resource = new ClientCustomerResource($this->entityManager, $container->get('settings'));
        parent::__construct($container, $uri);
    }

    public function getOne(Request $request, Response $response, array $args)
    {
        $customer = $this->entityManager->getRepository(ClientCustomer::class)->findOneById($args['customer_id']);
        if (!$customer) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusNotFound());
        }
        $representation = new CustomerRepresentation($customer);
        return Serialiser::returnSerialised($representation);
    }
}
