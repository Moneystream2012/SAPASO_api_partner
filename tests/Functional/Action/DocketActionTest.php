<?php
namespace Tests\Functional\Action;

use App\Action\DocketAction;
use Doctrine\ORM\EntityManager;
use Tests\ContainerAwareBaseTestCase;

/**
 * DocketActionTest test case.
 */
class DocketActionTest extends ContainerAwareBaseTestCase
{
    protected $clientCustomerId = 1;

    protected $nonExistsDocketId = 10000;
    protected $docketId = 1;

    /**
     * @var EntityManager $em
     */
    protected $em;

    /** @var @var DocketAction */
    protected $docketAction;

    /**
     *
     * @var EntityManager
     */
    protected $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->docketAction = new DocketAction($this->container);
        $this->entityManager = $this->container->get('em');
        $this->em = $this->container->get('em');
    }

    /**
     * Tests DocketAction->getOne()
     */
    public function testGetOne()
    {
        $uri = '/customer/' . $this->clientCustomerId . '/dockets/' . $this->nonExistsDocketId;
        $request = $this->createRequest('GET', $uri, []);
        $response = $this->sendHttpRequest($request);

        $this->assertStringContainsString(
            'status.entry_not_found',
            (string) $response->getBody(),
            "UN-expected result, entry MUST BE not found!"
        );

        $uri = '/customer/' . $this->clientCustomerId . '/dockets/' . $this->docketId;
        $request = $this->createRequest('GET', $uri, []);
        $response = $this->sendHttpRequest($request);
        $decodedBodyResponse = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('client_customer_id', $decodedBodyResponse);
        $this->assertEquals(1, $decodedBodyResponse['client_customer_id']);

        $this->assertArrayHasKey('booking_code', $decodedBodyResponse);
        $this->assertEquals("4739fcab990cacd4274b58f2e64af5zzcvxb", $decodedBodyResponse['booking_code']);

        $this->assertArrayHasKey('docket_lines', $decodedBodyResponse);
        $docketLines = $decodedBodyResponse['docket_lines'];

        $this->assertArrayHasKey('description', $docketLines[0]);
        $this->assertArrayHasKey('amount', $docketLines[0]);
        $this->assertEquals("-1000", $docketLines[0]['amount']);
        $this->assertArrayHasKey('type', $docketLines[0]);
        $this->assertEquals("MANUAL", $docketLines[0]['type']);

        $this->assertArrayHasKey('description', $docketLines[0]);
        $this->assertArrayHasKey('amount', $docketLines[0]);
        $this->assertArrayHasKey('type', $docketLines[0]);

        $this->assertArrayHasKey('description', $docketLines[1]);
        $this->assertArrayHasKey('amount', $docketLines[1]);
        $this->assertEquals("-2990", $docketLines[1]['amount']);
        $this->assertArrayHasKey('type', $docketLines[1]);
        $this->assertEquals("RETURN", $docketLines[1]['type']);
    }
}
