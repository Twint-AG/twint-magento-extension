<?php

declare(strict_types=1);

namespace Twint\Magento\Model;

use Magento\Quote\Model\Quote;

class CloneQuoteContext
{
    private ?Quote $quote = null;

    private ?Quote $originalQuote = null;

    public function hasQuote(): bool
    {
        return $this->quote instanceof Quote && $this->originalQuote instanceof Quote;
    }

    public function setQuote(Quote $quote, Quote $originalQuote): void
    {
        $this->quote = $quote;
        $this->originalQuote = $originalQuote;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function getOriginalQuote(): ?Quote
    {
        return $this->originalQuote;
    }
}
