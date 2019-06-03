<?php
namespace App\Action;

use App\Service\CoeoService;
use Sapaso\Entity\Docket;
use Sapaso\Resource\DocketResource;
use Sapaso\Service\ReturnStatus;
use Sapaso\Service\Serialiser;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Sapaso\Action\AbstractAction;

final class DocketAction extends AbstractAction
{
    /** @var DocketResource */
    protected $resource;

    /** @var CoeoService */
    protected $coeoService;


    public function __construct(Container $container)
    {
        parent::__construct($container, 'docket');

        $this->resource = new DocketResource($this->entityManager, $this->settings);
        $this->coeoService = new CoeoService($container);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     */
    public function getOne(Request $request, Response $response, array $args)
    {
        $customerId = (int)$args['customer_id'];
        $docketId = (int)$args['docket_id'];

        /** @var Docket $docket */
        $docket = $this->entityManager->getRepository(Docket::class)->findOneById($docketId);
        if (empty($docket) || $docket->getClientCustomer()->getId() != $customerId) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusNotFound());
        }

        return Serialiser::returnSerialised($docket, ['partnerList']);
    }

    /**
     * Override of the fetchPaginated class in the Abstract class, this to force
     * the $criteria filter. See the overridden class to see all documentation.
     *
     * The fields that can be used for searching are limited to what we define.
     *  - Search
     *  - Status
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return array|Response
     */
    public function getPaginated(Request $request, Response $response, array $args = [])
    {
        // filter by client customer id
        if (empty($customerId = (int)$args['customer_id'])) {
            return ReturnStatus::returnStatusIncomplete(['customer_id']);
        }

        // Create the ordering array
        list($field, $direction) = explode(':', $request->getParam('orderby'));
        $orderBy = [
            ($field ?: 'id') => ($direction ?: 'DESC'),
        ];

        // create the criteria array with the search options
        $criteria = [];
        // by client customer id
        if (!empty($customerId)) {
            $criteria[] = ['clientCustomer', 'JOIN'];
            $criteria['clientCustomer.id'] = $customerId;
        }
        // by status
        if (!empty($request->getParam('status'))) {
            $criteria['status'] = $request->getParam('status');
        }

        // by created field
        if (!empty($request->getParam('created_from'))) {
            if (!empty($request->getParam('created_to'))) {
                $criteria['created'] = [[$request->getParam('created_from'), $request->getParam('created_to')], 'BETWEEN'];
            } else {
                $criteria['created'] = [$request->getParam('created_from'), '>='];
            }
        } elseif (empty($request->getParam('created_from')) && !empty($request->getParam('created_to'))) {
            $criteria['created'] = [$request->getParam('created_to'), '<='];
        }

        // Add the search criteria by booking code
        if (!empty($search = $request->getParam('search'))) {
            $criteria['bookingCode'] = ["%{$search}%",'LIKE'];
        }

        $page = ($request->getParam('page')) < 1 ? 0: $request->getParam('page') -1;

        return $this->resource->getPaginated($criteria, $orderBy, null, $page, $this->uri, 'partnerList');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     */
    public function summary(Request $request, Response $response, array $args = [])
    {
        $docketGroups = $this->resource->docketsSummaryByCustomer($args['customer_id']);
        return Serialiser::returnSerialised($docketGroups);
    }

    /**
     * Send to COEO Docket, return docket with externalPartnerId & STATUS_DUNNING
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return array|Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function proceedDocket(Request $request, Response $response, array $args = [])
    {
        /** @var Docket $docket */
        $docket = $this->entityManager->getRepository(Docket::class)
            ->findOneById((int)$args['docket_id']);
        if (empty($docket)) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusNotFound());
        }

        $status = $docket->getStatus();
        if (!in_array($status, [Docket::STATUS_OPEN, Docket::STATUS_DUNNING])) {
            // @FIXME  All of these calls are all system initiated. So we need to log this responses
            return Serialiser::returnSerialised(ReturnStatus::returnStatusErrorWithPayload(
                null,
                $status,
                "Operation with docket in status '$status' not allowed"
            ));
        }

        $proceed = isset($args['proceed']) ? strtoupper($args['proceed']) : Docket::STATUS_DUNNING;
        switch ($proceed) {
            case Docket::STATUS_SOLD:
                $response = $this->coeoService->sellDocket($docket);
                if (is_array($response)) {
                    // @FIXME  All of these calls are all system initiated. So we need to log this responses
                    return Serialiser::returnSerialised(ReturnStatus::returnStatusApiUnknownError($response));
                }

                $docket->setStatus(Docket::STATUS_SOLD);
                break;

            case Docket::STATUS_DUNNING:
                $response = $this->coeoService->sendDocket($docket);
                if (is_array($response)) {
                    // @FIXME  All of these calls are all system initiated. So we need to log this responses
                    return Serialiser::returnSerialised(ReturnStatus::returnStatusApiUnknownError($response));
                }

                $docket
                    ->setStatus(Docket::STATUS_DUNNING)
                    ->setExternalPartnerId($response->fileNumber);
                break;

            default:
                return Serialiser::returnSerialised(ReturnStatus::returnStatusNotAllowed($proceed));
        }



        $this->entityManager->persist($docket);
        $this->entityManager->flush();

        return Serialiser::returnSerialised($docket, ['partnerList']);
    }
}
