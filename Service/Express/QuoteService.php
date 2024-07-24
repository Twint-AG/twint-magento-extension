<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteRepository;

class QuoteService
{
    public function __construct(
        private QuoteFactory    $quoteFactory,
        private CheckoutSession $checkoutSession,
        private QuoteRepository $quoteRepository
    )
    {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function clone(): array
    {
        // Get the current quote (cart)
        $currentQuote = $this->checkoutSession->getQuote();

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();

        foreach ($currentQuote->getData() as $key => $value) {
            if (in_array($key, ['id', 'entity_id', 'items', 'extension_attributes']))
                continue;

            $quote->setData($key, $value);
        }

        // Clone quote items
        foreach ($currentQuote->getAllVisibleItems() as $item) {
            $newItem = clone $item;
            $newItem->setId(null);
            $newItem->setQuote($quote);

            foreach ($item->getChildren() as $child){
                $cloned = clone $child;
                $cloned->setId(null);
                $cloned->setParentItem($newItem);
                $newItem->addChild($cloned);
            }

            $quote->addItem($newItem);
        }

        // Clone addresses
        if ($currentQuote->getBillingAddress()) {
            $quote->setBillingAddress(clone $currentQuote->getBillingAddress());
        }

        if ($currentQuote->getShippingAddress()) {
            $quote->setShippingAddress(clone $currentQuote->getShippingAddress());
        }

        // Clone payment
        if ($currentQuote->getPayment()) {
            $quote->setPayment(clone $currentQuote->getPayment());
        }

        // Save the new quote
        $this->quoteRepository->save($quote);

        return [$currentQuote, $quote];
    }

    public function removeAllItems(Quote $quote): Quote
    {
        $quote->removeAllItems();
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return $quote;
    }
}
