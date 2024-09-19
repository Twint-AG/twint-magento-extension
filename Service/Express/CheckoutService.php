<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Throwable;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Model\Api\ApiResponse;
use Twint\Magento\Service\ApiService;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Service\PairingService;
use Twint\Sdk\Value\CustomerDataScopes;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\ShippingMethod;
use Twint\Sdk\Value\ShippingMethodId;
use Twint\Sdk\Value\ShippingMethods;
use Twint\Sdk\Value\Version;

class CheckoutService
{
    public function __construct(
        private QuoteService $cartService,
        private readonly ClientBuilder $connector,
        private ApiService $api,
        private ShipmentEstimationInterface $shipmentEstimation,
        private AddressFactory $addressFactory,
        private PairingService $pairingService,
        private MonitorService $monitor
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Throwable
     */
    public function checkout()
    {
        list($currentQuote, $quote) = $this->cartService->clone();

        // Calculated when saving cart, just make sure here
        $quote->collectTotals();

        list($methods, $amount) = $this->getRequestParams($quote);
        $res = $this->callApi($quote, $methods, $amount);

        list($pairing) = $this->pairingService->createForExpress($res, $quote, $currentQuote, $amount);
        $this->monitor->status($pairing);

        return $pairing;
    }

    /**
     * @throws Throwable
     */
    private function callApi(Quote $quote, ShippingMethods $methods, float $amount): ApiResponse
    {
        $client = $this->connector->build($quote->getStoreId(), Version::NEXT);
        return $this->api->call(
            $client,
            'requestFastCheckOutCheckIn',
            [
                Money::CHF($amount),
                new CustomerDataScopes(...CustomerDataScopes::all()),
                $methods,
            ]
        );
    }

    protected function getRequestParams(Quote $quote): array
    {
        $amount = $quote->getGrandTotal();
        $options = [];

        /** @var Address $address */
        $address = $this->addressFactory->create();
        $address->setCountryId('CH');
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($quote->getId(), $address);

        foreach ($shippingMethods as $method) {
            $amount = $quote->getGrandTotal() - $method->getAmount();

            $options[] = new ShippingMethod(
                new ShippingMethodId($method->getMethodCode()),
                "{$method->getMethodTitle()}-{$method->getCarrierTitle()}",
                Money::CHF($method->getAmount())
            );
        }

        return [new ShippingMethods(...$options), $amount];
    }
}
