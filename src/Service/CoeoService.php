<?php
namespace App\Service;

use Doctrine\ORM\EntityManager;
use Slim\Container;
use Sapaso\Entity\Docket;
use Sapaso\Service\ApiCall;

class CoeoService
{
    const HEADERS_DEFAULT = [
        "Authorization: Basic {COEO_API_KEY}",
        "Sandbox: True",
        "User-Agent: MyUserAgent",
        "Accept: application/json",
        "Content-Type: application/json",
        "Host: api.coeo-mandanten.de:6066",
    ];

    const URL_COEO_SEND_CLAIM = "https://api.coeo-mandanten.de:6066/json.svc/Claim";
    const URL_COEO_SELL_CLAIM = "https://api.coeo-mandanten.de:6066/json.svc/claim/filenumber/";

    const COEO_TYPE_GOODS_WERE_SOLD  = 1;
    const COEO_TYPE_GOODS_WERE_PAID  = 2;
    const COEO_TYPE_SERVICE_RENDERED = 3;

    const COEO_STATUS_START_DEFAULT  = 10000;


    /** @var Container */
    protected $container;

    /** @var array Settings */
    protected $settings;
    
    protected $selfsigned;
    
    /** @var EntityManager */
    protected $entityManager;
    
    public function __construct(Container $container)
    {
        $this->selfsigned = true;
        $this->container = $container;
        $this->settings = $this->container->get('settings');
        $this->entityManager = $this->container->get('em');
    }

    /**
     * Will send Docket to COEO as new claim
     * if Ok, return (int)fileNumber, else return ['curl_error' => $err,'url' => $url]
     *
     * @param Docket $docket
     * @param float $returnDebitNoteFees
     *
     * @return object|array
     */
    public function sendDocket(Docket $docket, float $returnDebitNoteFees = 5.00)
    {
        $created = $docket->getCreated()->format('Y-m-d');
        $clientCustomer = $docket->getClientCustomer();
        $debtor = $clientCustomer->getPerson();
        $address = $debtor->getCurrentHomeAddress();

        $overdueFees = ($clientCustomer->getClient()->getOverdueFees() ?? 0) / 100;
        $amount = abs($docket->getAmount()) / 100;
        $outstandingSumm = $amount + $overdueFees + $returnDebitNoteFees;

        $headers = str_replace('{COEO_API_KEY}', getenv('COEO_API_KEY'), self::HEADERS_DEFAULT);

        $data = [
            "invoiceNo" => $docket->getBookingCode(),
            "type" => self::COEO_TYPE_SERVICE_RENDERED,
            "status" => self::COEO_STATUS_START_DEFAULT,
            "reason" => "Sapaso - need to pay to GYM",
            "originalValue" => $amount,
            "overdueFees" => $overdueFees,
            "returnDebitNoteFees" => $returnDebitNoteFees,
            "outstanding" => $outstandingSumm,
            "dateOfOrigin" => $created,
            "dateOfLastReminder" => $created,
            "note" => "",
            "profile" => 1,
            "annotation" => "",
            "catalogReason" => 40131,
            "catalogText" => "Sapaso - optional reason from GYM",
            "contractDate" => $created,
            "debtor" =>  [
                "customerDebtorId" => $clientCustomer->getId(),
                "salutationType" => $debtor->getGender() ?? "m",
                "firstName" => $debtor->getFirstName(),
                "lastName" => $debtor->getLastName(),
                "company" => "",
                "telephone1" => $debtor->getPhone() ?? "",
                "telephone2" => $debtor->getMobilePhone() ?? "",
                "fax" => "",
                "email" => $debtor->getEmail() ?? "",
                "birthDate" => $debtor->getDateOfBirth()->format('Y-m-d'),
                "debtorAddresses" => [[
                    "type" => 1,
                    "status" => (int)($address->getCheckStatus() == 'INCORRECT'),
                    "firstName" => $debtor->getFirstName(),
                    "lastName" => $debtor->getLastName(),
                    "company" => "",
                    "co" => "",
                    "postalCode" => $address->getPostalCode() ?? "",
                    "city" => $address->getCity() ?? "",
                    "country" => $address->getCountry() ?? "DE",
                    "street" => $address->getAddressLine1() .' '.
                        $address->getAddressLine2() .' '. $address->getAddressLine3(),
                ]]
            ]
        ];

        $response = (new ApiCall())
            ->send('POST', self::URL_COEO_SEND_CLAIM, $data, false, $headers, true);

        if (is_array($response)) {
            return $response;
        }

        $response = json_decode($response);
        return $response;
    }

    /**
     * Will cancel a COEO Claim (Docket)
     *
     * @param Docket $docket
     *
     * @return bool|string
     */
    public function sellDocket(Docket $docket)
    {
        $url = self::URL_COEO_SELL_CLAIM . $docket->getExternalPartnerId();

        $response = (new ApiCall())
            ->send('PUT', $url, false, false, self::HEADERS_DEFAULT, true);

        if (is_array($response)) {
            return $response;
        }

        return true;
    }
}
