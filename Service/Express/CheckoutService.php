<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Twint\Core\Setting\Settings;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Service\ApiService;
use Twint\Magento\Service\PairingService;
use Twint\Sdk\Value\CustomerDataScopes;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\ShippingMethod;
use Twint\Sdk\Value\ShippingMethodId;
use Twint\Sdk\Value\ShippingMethods;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Version;

class CheckoutService
{
    public function __construct(
        private QuoteService                $cartService,
        private readonly ClientBuilder      $connector,
        private ApiService                  $api,
        private ShipmentEstimationInterface $shipmentEstimation,
        private AddressFactory              $addressFactory,
        private PairingService              $pairingService,
    )
    {
    }

    public function capture(Pairing $pairing){
        $client = $this->connector->build($pairing->getStoreId());

        /** @var non-empty-string $orderId */
        $orderId = $order->getId();

        return $this->api->call($client, 'startFastCheckoutOrder', [
            PairingUuid::fromString($pairing->getId()),
            new UnfiledMerchantTransactionReference($orderId),
            new Money($order->getCurrency()?->getIsoCode() ?? TwintConstant::CURRENCY, $order->getAmountTotal()),
        ], true);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function checkout()
    {
        list($currentQuote, $quote) = $this->cartService->clone();

        // Calculated when saving cart, just make sure here
        $quote->collectTotals();

        $res = $this->callApi($quote);
        list ($pairing) = $this->pairingService->createForExpress($res, $quote, $currentQuote);

        return $pairing;
    }

    private function callApi(Quote $quote): ApiResponse
    {
        $client = $this->connector->build($quote->getStoreId(), Version::NEXT);

        return $this->api->call(
            $client,
            'requestFastCheckOutCheckIn',
            [
                Money::CHF($quote->getGrandTotal()),
                new CustomerDataScopes(...CustomerDataScopes::all()),
                $this->getShippingOptions($quote),
            ]
        );
    }

    protected function getShippingOptions($quote): ShippingMethods
    {
        $options = [];

        /** @var Address $address */
        $address = $this->addressFactory->create();
        $address->setCountryId('CH');
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($quote->getId(), $address);

        foreach ($shippingMethods as $method) {
            $options[] = new ShippingMethod(
                new ShippingMethodId($method->getMethodCode()),
                "{$method->getMethodTitle()}-{$method->getCarrierTitle()}",
                Money::CHF($method->getAmount())
            );
        }

        return new ShippingMethods(...$options);
    }
}
