<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressModel;
use Magento\Quote\Model\ResourceModel\Quote as ResourceModel;
use Throwable;
use Twint\Magento\Builder\ClientBuilder;
use Twint\Magento\Constant\TwintConstant;
use Twint\Magento\Exception\CheckoutException;
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
        private MonitorService $monitor,
        private ResourceModel $quoteModel,
        private QuoteRepository $quoteRepository,
        private AddressModel $addresResourceModel,
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Throwable
     */
    public function checkout(Product|bool $product = null, $request = null)
    {
        /** @var Quote $quote */
        list($currentQuote, $quote) = $this->cartService->clone();

        if ($product instanceof Product) {
            $result = $quote->addProduct($product, $request);
            if (is_string($result)) {
                throw new CheckoutException($result);
            }
            $this->quoteModel->save($quote);
        }
        $quote = $this->quoteRepository->get($quote->getId());

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
            [Money::CHF($amount), new CustomerDataScopes(...CustomerDataScopes::all()), $methods]
        );
    }

    protected function getRequestParams(Quote $quote): array
    {
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $baseAmount = $quote->getGrandTotal();

        $options = [];

        $address = $this->addressFactory->create();
        $address->setCountryId('CH');
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($quote->getId(), $address);

        foreach ($shippingMethods as $method) {
            $shipping = $quote->getShippingAddress();
            if (!$shipping) {
                $shipping = clone $address;
                $shipping->setSameAsBilling(1);
                $shipping->setQuoteId($quote->getId());
            }
            $shipping->setShippingMethod("{$method->getCarrierCode()}_{$method->getMethodCode()}");

            $this->addresResourceModel->save($shipping);

            $quote = $this->quoteRepository->get($quote->getId());

            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();

            $amount = $quote->getGrandTotal();

            $separator = TwintConstant::SHIPPING_METHOD_SEPARATOR;

            $options[] = new ShippingMethod(
                new ShippingMethodId("{$method->getCarrierCode()}{$separator}{$method->getMethodCode()}"),
                "{$method->getMethodTitle()}-{$method->getCarrierTitle()}",
                Money::CHF(max($amount - $baseAmount, 0))
            );
        }

        if (isset($shipping) && $shipping instanceof Quote\Address) {
            $shipping->setShippingMethod((string) null);
            $this->addresResourceModel->save($shipping);
        }

        return [new ShippingMethods(...$options), $baseAmount];
    }
}
