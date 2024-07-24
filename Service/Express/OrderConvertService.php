<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;


use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Checkout\Model\ShippingInformation;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Twint\Magento\Model\Method\TwintExpressMethod;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;

class OrderConvertService
{
    private CartExtensionFactory $cartExtensionFactory;

    protected ShippingAssignmentFactory $shippingAssignmentFactory;

    private $shippingFactory;

    public function __construct(
        private readonly ServiceInputProcessor                  $serviceInputProcessor,
        private readonly CartRepositoryInterface                $quoteRepository,
        private readonly QuoteIdMaskFactory                     $quoteIdMaskFactory,
        private readonly ShippingInformationManagementInterface $shippingInformationManagement,
        private readonly QuoteAddressValidator                  $addressValidator,
        private CartManagementInterface $quoteManagement,
        CartExtensionFactory                                    $cartExtensionFactory = null,
        ShippingAssignmentFactory                               $shippingAssignmentFactory = null,
        ShippingFactory                                         $shippingFactory = null
    )
    {
        $this->cartExtensionFactory = $cartExtensionFactory ?: ObjectManager::getInstance()
            ->get(CartExtensionFactory::class);
        $this->shippingAssignmentFactory = $shippingAssignmentFactory ?: ObjectManager::getInstance()
            ->get(ShippingAssignmentFactory::class);
        $this->shippingFactory = $shippingFactory ?: ObjectManager::getInstance()
            ->get(ShippingFactory::class);
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws CouldNotSaveException
     */
    public function convert(Pairing $pairing, PairingHistory $history)
    {
        $quote = $this->quoteRepository->get($pairing->getQuoteId());

        $markedId = $this->createMarkedId($quote);
        $shippingInputs = $this->setShippingInformation($markedId, $pairing->getCustomerData());

        /** @var ShippingInformation $shipping */
        $shipping = $shippingInputs[1];
        $shipping->setShippingCarrierCode($pairing->getShippingId());
        $shipping->setShippingMethodCode($pairing->getShippingId());

        $this->saveAddress($shipping, $quote);
        $this->setPaymentMethod($quote);

        $orderId =  $this->quoteManagement->placeOrder($quote->getId());

    }

    private function setPaymentMethod(Quote $quote): void
    {
        $payment = $quote->getPayment();
        $payment->setMethod(TwintExpressMethod::CODE);

        $this->quoteRepository->save($quote);
    }

    private function saveAddress(ShippingInformation $addressInformation, $quote): void
    {
        $address = $addressInformation->getShippingAddress();

        if (!$address->getCustomerAddressId()) {
            $address->setCustomerAddressId(null);
        }

        $billingAddress = $addressInformation->getBillingAddress();
        if ($billingAddress) {
            if (!$billingAddress->getCustomerAddressId()) {
                $billingAddress->setCustomerAddressId(null);
            }
            $this->addressValidator->validateForCart($quote, $billingAddress);
            $quote->setBillingAddress($billingAddress);
        }

        $this->addressValidator->validateForCart($quote, $address);
        $carrierCode = $addressInformation->getShippingCarrierCode();
        $address->setLimitCarrier($carrierCode);
        $methodCode = $addressInformation->getShippingMethodCode();
        $quote = $this->prepareShippingAssignment($quote, $address, $carrierCode . '_' . $methodCode);

        $quote->setIsMultiShipping(0);

        $this->quoteRepository->save($quote);

    }

    private function prepareShippingAssignment(
        CartInterface    $quote,
        AddressInterface $address,
        string           $method
    ): CartInterface
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }

        $shipping = $shippingAssignment->getShipping();
        if ($shipping === null) {
            $shipping = $this->shippingFactory->create();
        }

        $shipping->setAddress($address);
        $shipping->setMethod($method);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        return $quote->setExtensionAttributes($cartExtension);
    }

    /**
     * @throws Exception
     */
    private function setShippingInformation(string $markedId, string $customerDataString)
    {
        $data = json_decode($customerDataString, true);
        $shipping = $data['shipping_address'];

        $address = [
            "countryId" => $shipping['country'],
            "region" => "",
            "street" => [
                $shipping['street'],
                ""
            ],
            "company" => "",
            "telephone" => $data['phone_number'],
            "postcode" => $shipping['zip'],
            "city" => $shipping['city'],
            "firstname" => $shipping['firstName'],
            "lastname" => $shipping['lastName'],
            'email' => $data['email']
        ];

        $input = [
            'addressInformation' => [
                'shipping_address' => $address,
                'billing_address' => $address
            ],
            'cartId' => $markedId
        ];

        return $this->serviceInputProcessor->process(
            'Magento\Checkout\Api\GuestShippingInformationManagementInterface',
            'saveAddressInformation', $input
        );
    }

    protected function createMarkedId(Quote $quote)
    {
        // Load or create the quote ID mask for the quote
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quote->getId(), 'quote_id');
        if (!$quoteIdMask->getMaskedId()) {
            $quoteIdMask->setQuoteId($quote->getId())->save();
        }

        // Return the masked ID
        return $quoteIdMask->getMaskedId();
    }
}
