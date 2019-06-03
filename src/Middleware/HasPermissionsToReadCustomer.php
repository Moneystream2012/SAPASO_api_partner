<?php
namespace App\Middleware;

use Doctrine\ORM\EntityManager;
use Sapaso\Entity\Client;
use Sapaso\Entity\ClientCustomer;
use Sapaso\Entity\Partner;
use Psr\Container\ContainerInterface as Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Sapaso\Service\ReturnStatus;
use Sapaso\Service\Serialiser;

class HasPermissionsToReadCustomer
{
    /** @var Container */
    private $container;

    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $customerWildcard;

    public function __construct(Container $container, string $customerWildcard)
    {
        $this->container = $container;
        $this->entityManager = $container->get('em');
        $this->customerWildcard = $customerWildcard;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $route = $request->getAttribute('route');
        // Return NOTFOUND if route is null
        if ($route == null) {
            $handler = $this->container['notFoundHandler'];
            return $handler($request, $response);
        }
        // Retrieve and check customer_id in both the route and in the URI
        $customerId = $route->getArgument($this->customerWildcard) ?: (int)$request->getParam($this->customerWildcard, null);
        if ($customerId === null) {
            $handler = $this->container['notAllowedHandler'];
            return $handler($request, $response);
        }

        /** @var ClientCustomer $clientCustomer */
        $clientCustomer = $this->entityManager->getRepository(ClientCustomer::class)->findOneById($customerId);
        $client = $clientCustomer->getClient();

        /** @var Partner $currentPartner */
        $currentPartner = $this->container->get('settings')['partner'];
        if (!$currentPartner || false == $this->isCurrentCustomerAllowedForPartner($currentPartner, $client)) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusNoAccess($customerId));
        }

        return $next($request, $response);
    }

    /**
     * @param Partner $currentPartner
     * @param Client $client
     *
     * @return bool
     */
    private function isCurrentCustomerAllowedForPartner(Partner $currentPartner, Client $client)
    {
        return in_array(
            $currentPartner->getId(),
            [
                $client->getDunningPartner()->getId(),
                $client->getCollectionPartner()->getId()]
        );
    }
}
