<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Quote;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressModel;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate as RateModel;
use Magento\Quote\Model\ResourceModel\Quote as QuoteModel;
use Magento\Quote\Model\ResourceModel\Quote\Item as ItemModel;
use Magento\Quote\Model\ResourceModel\Quote\Payment as PaymentModel;
use Throwable;

class QuoteRepository
{
    public function __construct(
        private readonly QuoteModel       $resourceModel,
        private readonly AddressModel     $addressModel,
        private readonly ItemModel        $itemModel,
        private readonly RateModel        $rateModel,
        private readonly PaymentModel     $paymentModel,
        private readonly QuoteFactory     $factory,
        private readonly Monolog          $logger,
        private readonly AddressFactory   $addressFactory
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function clone(Quote $original, Quote $new, bool $expressCheckout = false): Quote
    {
        try {
            $this->resourceModel->save($new);
            $this->saveItems($original, $new);
            $this->saveAddresses($original, $new, $expressCheckout);

            $this->savePayment($original, $new);
        } catch (Throwable $e) {
            $this->logger->error('Cannot save entity ' . $e->getMessage());
        }

        return $this->getById($new->getId());
    }

    /**
     * @throws AlreadyExistsException
     */
    private function savePayment(Quote $original, Quote $new): void
    {
        $payment = $original->getPayment();
        $cloned = clone $payment;

        $cloned->setId(null);
        $cloned->getEntityId(null);
        $cloned->setQuoteId($new->getId());
        $cloned->setQuote($new);

        $this->paymentModel->save($cloned);
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveItems(Quote $original, Quote $new): array
    {
        $map = [];

        /** @var Item $item */
        foreach ($original->getAllVisibleItems() as $item) {
            $clonedItem = clone $item;

            $clonedItem->setId(null);
            $clonedItem->setItemId(null);
            $clonedItem->setCreatedAt(null);
            $clonedItem->setUpdatedAt(null);
            $clonedItem->setQuoteId($new->getId());
            $clonedItem->setQuote($new);

            $this->itemModel->save($clonedItem);

            $map[$item->getId()] = $clonedItem;

            /** @var Item $child */
            foreach ($item->getChildren() as $child) {
                $clonedChild = clone $child;

                $clonedChild->setId(null);
                $clonedChild->setItemId(null);
                $clonedChild->setCreatedAt(null);
                $clonedChild->setUpdatedAt(null);
                $clonedChild->setQuoteId($new->getId());
                $clonedChild->setQuote($new);
                $clonedChild->setParentItemId($clonedItem->getId());
                $clonedChild->setParentItem($clonedItem);

                $this->itemModel->save($clonedChild);
                $map[$child->getId()] = $clonedChild;
            }
        }

        return $map;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveAddresses(Quote $original, Quote $new, bool $expressCheckout): void
    {
        $addresses = [
            'shipping' => $original->getShippingAddress(),
            'billing' => $original->getBillingAddress(),
        ];

        foreach ($addresses as $type => $address) {
            if ($address instanceof Address) {
                $this->cloneAddress($new, $address, $expressCheckout);
            } else {
                $address = $this->buildAddress($new);
                $address->setAddressType($type);
                $this->addressModel->save($address);
            }
        }
    }

    private function cloneAddress(Quote $new, Address $address, bool $expressCheckout): void
    {
        $clonedAddress = clone $address;

        $clonedAddress->setId(null);
        $clonedAddress->setQuoteId($new->getId());
        $clonedAddress->setQuote($new);

        // only set country
        if ($expressCheckout) {
            $clonedAddress->setCountryId('CH');
            $clonedAddress->setRegionCode(null);
            $clonedAddress->setRegionId(null);
            $clonedAddress->setPostcode(null);
            $clonedAddress->setShippingMethod((string) null);
        }

        $this->addressModel->save($clonedAddress);

        // if express checkout skip all shipping rates
        if (!$expressCheckout) {
            /** @var Address\Rate $rate */
            foreach ($address->getAllShippingRates() as $rate) {
                $clonedRate = clone $rate;

                $clonedRate->setId(null);
                $clonedRate->setAddressId($clonedAddress->getId());
                $clonedRate->setAddress($clonedAddress);

                $this->rateModel->save($clonedRate);
            }
        }
    }

    private function buildAddress(Quote $quote): Address
    {
        $address = $this->addressFactory->create();
        $address->setQuoteId($quote->getId());
        $address->setQuote($quote);
        $address->setCountryId('CH');
        $address->setRegionCode(null);
        $address->setRegionId(null);
        $address->setPostcode(null);
        $address->setShippingMethod((string) null);

        return $address;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $entity = $this->factory->create();
        $this->resourceModel->load($entity, $id);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__("Requested item doesn't exist"));
        }

        return $entity;
    }

    /**
     * @throws AlreadyExistsException
     */
    public function save(Quote $quote): void
    {
        $this->resourceModel->save($quote);
    }
}
