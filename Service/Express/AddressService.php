<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressModel;
use Twint\Magento\Model\Method\TwintExpressMethod;
use Twint\Magento\Model\Pairing;

class AddressService
{
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly AddressModel $addressResourceModel,
    ) {
    }

    public function handle(Pairing $pairing, Quote $quote): void
    {
        $this->handleAddresses($quote, $pairing);
        $this->setPaymentMethod($quote);
    }

    private function getPaymentAddress(Pairing $pairing): array
    {
        $data = json_decode($pairing->getCustomerData(), true);
        $shipping = $data['shipping_address'];

        return [
            'countryId' => $shipping['country'],
            'region' => '',
            'street' => [$shipping['street'], ''],
            'company' => '',
            'telephone' => $data['phone_number'],
            'postcode' => $shipping['zip'],
            'city' => $shipping['city'],
            'firstname' => $shipping['firstName'],
            'lastname' => $shipping['lastName'],
            'email' => $data['email'],
        ];
    }

    private function handleAddresses(Quote $quote, Pairing $pairing): void
    {
        $shippingMethod = str_replace('+', '_',$pairing->getShippingId());
        $addressData = $this->getPaymentAddress($pairing);

        /** @var Quote\Address $address */
        foreach ($quote->getAllAddresses() as $address){
            $address->setPostcode($addressData['postcode'] ?? '');
            $address->setEmail($addressData['email'] ?? '');
            $address->setCity($addressData['city'] ?? '');
            $address->setFirstname($addressData['firstname'] ?? '');
            $address->setLastname($addressData['lastname'] ?? '');
            $address->setStreet($addressData['street'][0] ?? '');



            $address->setShippingMethod($shippingMethod);

            $this->addressResourceModel->save($address);
        }
    }

    private function setPaymentMethod(Quote $quote): void
    {
        $payment = $quote->getPayment();
        $payment->setMethod(TwintExpressMethod::CODE);

        $this->quoteRepository->save($quote);
    }
}
