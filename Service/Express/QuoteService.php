<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteModel;
use Twint\Magento\Model\Quote\QuoteRepository;

class QuoteService
{
    public function __construct(
        private readonly QuoteFactory $factory,
        private readonly CheckoutSession $checkoutSession,
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteModel $resourceModel,
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function clone(): array
    {
        // Get the current quote (cart)
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getId()) {
            $this->resourceModel->save($quote);
        }

        $cloned = $this->factory->create();

        foreach ($quote->getData() as $key => $value) {
            if (in_array($key, ['id', 'entity_id', 'items', 'extension_attributes'], true)) {
                continue;
            }

            $cloned->setData($key, $value);
        }

        // Save the new quote
        $cloned = $this->quoteRepository->clone($quote, $cloned, true);

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
