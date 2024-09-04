<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Twint\Magento\Model\Quote\QuoteRepository;

class QuoteService
{
    public function __construct(
        private readonly QuoteFactory             $factory,
        private readonly CheckoutSession          $checkoutSession,
        private readonly QuoteRepository $quoteRepository,
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
        $quote = $this->checkoutSession->getQuote();

        $cloned = $this->factory->create();

        foreach ($quote->getData() as $key => $value) {
            if (in_array($key, ['id', 'entity_id', 'items', 'extension_attributes']))
                continue;

            $cloned->setData($key, $value);
        }

        // Save the new quote
        $cloned = $this->quoteRepository->clone($quote, $cloned);

        return [$quote, $cloned];
    }

    public function removeAllItems(Quote $quote): Quote
    {
        $quote->removeAllItems();
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return $quote;
    }
}
