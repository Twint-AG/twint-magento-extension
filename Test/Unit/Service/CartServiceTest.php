<?php

namespace Tests\Unit\Twint\Magento\Service;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Twint\Magento\Model\Quote\QuoteRepository;
use Twint\Magento\Service\CartService;

class CartServiceTest extends TestCase
{
    private CartService $cartService;
    private MockInterface $quoteFactory;
    private MockInterface $quoteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteFactory = Mockery::mock(QuoteFactory::class);
        $this->quoteRepository = Mockery::mock(QuoteRepository::class);

        $this->cartService = new CartService(
            $this->quoteFactory,
            $this->quoteRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testClone(): void
    {
        $originalQuote = Mockery::mock(Quote::class);
        $clonedQuote = Mockery::mock(Quote::class);

        $originalQuote->shouldReceive('getData')->andReturn([
            'customer_id' => 1,
            'store_id' => 1,
            'id' => 100,
            'items' => [],
        ]);

        $clonedQuote->shouldReceive('setData')->with('customer_id', 1)->once();
        $clonedQuote->shouldReceive('setData')->with('store_id', 1)->once();

        $this->quoteFactory->shouldReceive('create')->andReturn($clonedQuote);
        $this->quoteRepository->shouldReceive('clone')
            ->with($originalQuote, $clonedQuote)
            ->andReturn($clonedQuote);

        $result = $this->cartService->clone($originalQuote);

        $this->assertSame($clonedQuote, $result);
    }

    public function testDeActivate(): void
    {
        $quoteId = 1;
        $quote = Mockery::mock(Quote::class);

        $this->quoteRepository->shouldReceive('getById')
            ->with($quoteId)
            ->andReturn($quote);

        $quote->shouldReceive('setIsActive')
            ->with(false)
            ->once();

        $this->quoteRepository->shouldReceive('save')
            ->with($quote)
            ->once();

        $this->cartService->deActivate($quoteId);
    }

    public function testRemoveAllItemsWithQuoteObject(): void
    {
        $quote = Mockery::mock(Quote::class);

        $quote->shouldReceive('removeAllItems')->once();
        $quote->shouldReceive('collectTotals')->once();

        $this->quoteRepository->shouldReceive('save')
            ->with($quote)
            ->once();

        $result = $this->cartService->removeAllItems($quote);

        $this->assertSame($quote, $result);
    }

    public function testRemoveAllItemsWithQuoteId(): void
    {
        $quoteId = 1;
        $quote = Mockery::mock(Quote::class);

        $this->quoteRepository->shouldReceive('getById')
            ->with($quoteId)
            ->andReturn($quote);

        $quote->shouldReceive('removeAllItems')->once();
        $quote->shouldReceive('collectTotals')->once();

        $this->quoteRepository->shouldReceive('save')
            ->with($quote)
            ->once();

        $result = $this->cartService->removeAllItems($quoteId);

        $this->assertSame($quote, $result);
    }
}
