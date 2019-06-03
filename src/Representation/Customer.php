<?php
namespace App\Representation;

use Sapaso\Entity\ClientCustomer;
use JMS\Serializer\Annotation as JMS;
use Sapaso\Entity\Person;

/**
 * Representation of a customer for our partners
 * @package App\Representation
 * @JMS\ExclusionPolicy("NONE")
 */
class Customer
{
    /**
     * @var ClientCustomer
     * @JMS\Exclude()
     */
    protected $clientCustomer;

    /**
     * @internal For quick access
     * @var Person
     * @JMS\Exclude()
     */
    protected $person;

    /**
     * Representation constructor.
     * @param ClientCustomer $clientCustomer
     */
    public function __construct(ClientCustomer $clientCustomer)
    {
        $this->clientCustomer = $clientCustomer;
        $this->person = $clientCustomer->getPerson();
    }

    /**
     * @return int
     * @JMS\VirtualProperty()
     */
    public function id()
    {
        return $this->clientCustomer->getId();
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function name()
    {
        $fullName = $this->person->getFirstName() . ' ' . $this->person->getLastName();
        return $fullName;
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function title()
    {
        return $this->person->getTitle();
    }

    /**
     * @return array
     * @JMS\VirtualProperty()
     */
    public function address()
    {
        $clientAddress = $this->clientCustomer->getAddress();
        $addressRepresentation = [];
        if ($clientAddress) {
            $addressRepresentation = [
                'address_line1' => $clientAddress->getAddressLine1(),
                'address_line2' => $clientAddress->getAddressLine2(),
                'address_line3' => $clientAddress->getAddressLine3(),
                'postal_code'   => $clientAddress->getPostalCode(),
                'city'          => $clientAddress->getCity(),
                'country'       => $clientAddress->getCountry()
            ];
        }

        return $addressRepresentation;
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function email()
    {
        return $this->person->getEmail();
    }

    /**
     * @return array
     * @JMS\VirtualProperty()
     */
    public function phoneNumbers()
    {
        $phoneNumbers = [
            'mobile' => $this->person->getMobilePhone(),
            'cell'   => $this->person->getPhone()
        ];
        return $phoneNumbers;
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function iban()
    {
        return $this->clientCustomer->getIban();
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function payToIban()
    {
        return 'N/A';
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function accountHolder()
    {
        return 'N/A';
    }

    /**
     * @return string
     * @JMS\VirtualProperty()
     */
    public function signatureDate()
    {
        return 'N/A';
    }
}
