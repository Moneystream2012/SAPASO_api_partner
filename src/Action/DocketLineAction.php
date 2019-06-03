<?php
namespace App\Action;

use Sapaso\Entity\Docket;
use Sapaso\Entity\DocketLine;
use Sapaso\Entity\Partner;
use Sapaso\Entity\SepaEntry;
use Sapaso\Resource\DocketResource;
use \Sapaso\Service\Serialiser;
use \Sapaso\Service\ReturnStatus;
use \Sapaso\Action\AbstractAction;
use \Sapaso\Helper\ArrayUtils;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

final class DocketLineAction extends AbstractAction
{
    const REQUIRED_FIELDS = ['docket_id', 'amount', 'sepa_entry_id'];

    protected $validation;

    /**
     * @inheritdoc
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->resource = new DocketResource($this->entityManager, $this->settings);
        $this->validation = new ArrayUtils();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function addDocketLine(Request $request, Response $response, array $args = [])
    {
        $formData = $request->getParsedBody();
        $formData['docket_id'] = $args['docket_id'];

        $missingFields = $this->testFormData($formData);
        if ($missingFields !== []) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusIncomplete($missingFields));
        }

        $docket = $this->entityManager->getRepository(Docket::class)->find($formData['docket_id']);
        if (empty($docket)) {
            return Serialiser::returnSerialised(ReturnStatus::returnStatusNotFound());
        }

        if (empty($formData['type']) || !in_array($formData['type'], [DocketLine::TYPE_MANUAL, DocketLine::TYPE_RETURN,
                DocketLine::TYPE_RETURN_CHARGE, DocketLine::TYPE_INITIAL, DocketLine::TYPE_COST])) {
            $formData['type'] = DocketLine::TYPE_MANUAL;
        }

        /** @var Partner $partner */
        $partner = $this->container->get('settings')['partner'];

        $docketLine = (new DocketLine())
            ->setDocket($docket)
            ->setAmount($formData['amount'])
            ->setType($formData['type'])
            ->setDescription(empty($formData['description']) ? null : $formData['description'])
            ->setAddedBy($partner->getId());

        /** @var SepaEntry $sepaEntry */
        $sepaEntry = $this->entityManager->getRepository(SepaEntry::class)->find($formData['sepa_entry_id']);
        if ($sepaEntry && $args['customer_id'] == $sepaEntry->getClientCustomer()->getId()) {
            $docketLine->setSepaEntry($sepaEntry);
        }

        $this->entityManager->persist($docketLine);
        $this->entityManager->flush();

        return Serialiser::returnSerialised($docketLine, ['partnerList']);
    }

    /**
     * Function perform input data validation for DocketLine
     *
     * @param array $formData
     *
     * @return array
     */
    private function testFormData($formData)
    {
        $params = [
            'validationType' => 'required',
            'formData' => $formData,
            'fields'   => self::REQUIRED_FIELDS,
        ];
        $validation = $this->validation->checkInput($params);

        return ($validation['status'] === 'error') ? $validation['fields'] : [];
    }
}
