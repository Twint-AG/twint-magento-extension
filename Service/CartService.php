<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Twint\Magento\Model\Quote\QuoteRepository;

class CartService
{
    public function __construct(
        private readonly QuoteFactory    $factory,
        private readonly QuoteRepository $quoteRepository,
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function clone(Quote $quote): Quote
    {
        /** @var Quote $quote */
        $cloned = $this->factory->create();

        foreach ($quote->getData() as $key => $value) {
            if (in_array($key, ['id', 'entity_id', 'items', 'extension_attributes']))
                continue;

            $cloned->setData($key, $value);
        }

        // Save the new quote
        return $this->quoteRepository->clone($quote, $cloned);
    }

    public function deActivate(int $quoteId): void
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getById($quoteId);
        $quote->setIsActive(false);

        $this->quoteRepository->save($quote);
    }

    public function removeAllItems(Quote|int $quote): Quote
    {
        if(is_int($quote)){
            $quote = $this->quoteRepository->getById($quote);
        }

        $quote->removeAllItems();
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return $quote;
    }
}
