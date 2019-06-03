<?php
namespace Tests\Functional\Action;

use App\Action\DocketLineAction;
use Doctrine\ORM\EntityManager;
use Sapaso\Entity\DocketLine;
use Tests\ContainerAwareBaseTestCase;

/**
 * DocketLineActionTest test case.
 */
class DocketLineActionTest extends ContainerAwareBaseTestCase
{
    protected $clientCustomerId = 1;

    protected $docketId = 1;

    /**
     * @var EntityManager $em
     */
    protected $em;

    /** @var @var DocketLineAction */
    protected $docketLineAction;

    /**
     *
     * @var EntityManager
     */
    protected $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->docketLineAction = new DocketLineAction($this->container);
        $this->entityManager = $this->container->get('em');
        $this->em = $this->container->get('em');
    }

    /**
     * Tests DocketLineAction->getOne()
     */
    public function testAddDocketLine()
    {
        $formData = [
            "amount" => 1000,
            "sepa_entry_id" => 2,
            "type" => DocketLine::TYPE_MANUAL,
            "description" => "Test description",
        ];

        $uri = '/customer/' . $this->clientCustomerId . '/dockets/' . $this->docketId . '/docketLine';
        $request = $this->createRequest('POST', $uri, $formData);
        $response = $this->sendHttpRequest($request);

        $this->assertStringContainsString(
            '"error_code":"0","text":"successful"',
            (string) $response->getBody(),
            "UN-expected result, response-code must be 0!"
        );

        $uri = '/customer/' . $this->clientCustomerId . '/dockets/' . $this->docketId;
        $request = $this->createRequest('GET', $uri, []);
        $response = $this->sendHttpRequest($request);
        $decodedBodyResponse = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('docket_lines', $decodedBodyResponse);
        $docketLines = $decodedBodyResponse['docket_lines'];

        $this->assertArrayHasKey('amount', $docketLines[2]);
        $this->assertEquals($formData['amount'], $docketLines[2]['amount']);

        $this->assertArrayHasKey('type', $docketLines[2]);
        $this->assertEquals($formData['type'], $docketLines[2]['type']);

        $this->assertArrayHasKey('description', $docketLines[2]);
        $this->assertEquals($formData['description'], $docketLines[2]['description']);
    }
}
