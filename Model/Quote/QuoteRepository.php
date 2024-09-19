<?php

declare(strict_types=1);

namespace Twint\Magento\Model\Quote;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressModel;
use Magento\Quote\Model\ResourceModel\Quote\Address\Item as AddressItemModel;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate as RateModel;
use Magento\Quote\Model\ResourceModel\Quote as QuoteModel;
use Magento\Quote\Model\ResourceModel\Quote\Item as ItemModel;
use Magento\Quote\Model\ResourceModel\Quote\Payment as PaymentModel;
use Throwable;

class QuoteRepository
{
    public function __construct(
        private readonly QuoteModel $resourceModel,
        private readonly AddressModel $addressModel,
        private readonly ItemModel $itemModel,
        private readonly AddressItemModel $addressItemModel,
        private readonly RateModel $rateModel,
        private readonly PaymentModel $paymentModel,
        private readonly QuoteFactory $factory,
        private readonly Monolog $logger
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function clone(Quote $original, Quote $new): Quote
    {
        try {
            $this->resourceModel->save($new);
            $itemsMapping = $this->saveItems($original, $new);
            $addressMapping = $this->saveAddresses($original, $new);
            $this->shippingAddressItems($original, $itemsMapping, $addressMapping);
            $this->savePayment($original, $new);
        } catch (Throwable $e) {
            $this->logger->error('Cannot save entity ' . $e->getMessage());
        }

        return $this->getById($new->getId());
    }

    private function shippingAddressItems(Quote $original, array $itemsMapping, array $addressMapping)
    {
        /** @var Address\Item $item */
        foreach ($original->getShippingAddressesItems() as $item) {
            if ($item instanceof Item) {
                continue;
            }

            $cloned = clone $item;
            $cloned->setId(null);
            $cloned->setEntityId(null);

            $cloned->setQuoteItemId($itemsMapping[$item->getQuoteItemId()]->getId());

            $cloned->setQuoteAddressId($addressMapping[$item->getQuoteAddressId()]->getId());
            $cloned->setAddress($addressMapping[$item->getQuoteAddressId()]);

            $this->addressItemModel->save($cloned);

            foreach ($item->getChildren() as $child) {
                $clonedChild = clone $item;

                $clonedChild->setId(null);
                $clonedChild->setEntityId(null);

                $clonedChild->setQuoteItemId($itemsMapping[$item->getQuoteItemId()]->getId());

                $clonedChild->setQuoteAddressId($addressMapping[$item->getQuoteAddressId()]->getId());
                $clonedChild->setAddress($addressMapping[$item->getQuoteAddressId()]);

                $clonedChild->setParentItemId($cloned->getId());

                $this->addressItemModel->save($clonedChild);
            }
        }
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

            $clonedItem->setCreatedAt(null);
            $clonedItem->setUpdatedAt(null);
            $clonedItem->setQuoteId($new->getId());
            $clonedItem->setQuote($new);
            $this->itemModel->save($clonedItem);

            $map[$item->getId()] = $clonedItem;

            /** @var Item $child */
            foreach ($item->getChildren() as $child) {
                $clonedChild = clone $child;

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
    private function saveAddresses(Quote $original, Quote $new): array
    {
        $map = [];
        foreach ($original->getAllAddresses() as $address) {
            $clonedAddress = clone $address;

            $clonedAddress->setId(null);
            $clonedAddress->setQuoteId($new->getId());
            $clonedAddress->setQuote($new);
            $clonedAddress->setCountryId('CH');

            $this->addressModel->save($clonedAddress);

            $map[$address->getId()] = $clonedAddress;

            /** @var Address\Rate $rate */
            foreach ($address->getAllShippingRates() as $rate) {
                $clonedRate = clone $rate;

                $clonedRate->setId(null);
                $clonedRate->setAddressId($clonedAddress->getId());
                $clonedRate->setAddress($clonedAddress);

                $this->rateModel->save($clonedRate);
            }
        }

        return $map;
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
