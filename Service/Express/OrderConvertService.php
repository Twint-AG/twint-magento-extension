<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Service\PairingService;

class OrderConvertService
{
    public function __construct(
        private CartManagementInterface $quoteManagement,
        private PairingService $pairingService,
        private AddressService $addressService,
        private QuoteService $quoteService,
        private OrderRepository $orderRepository,
        private QuoteRepository $quoteRepository,
        private OrderSender $orderSender,
        private Emulation $emulate,
        private StoreManagerInterface $storeManager,
        private ResolverInterface $localeResolver,
        private LoggerInterface $logger,
        private RendererInterface $phraseRenderer
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function convert(Pairing $pairing, ?PairingHistory $history = null): ?string
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($pairing->getQuoteId());

        // Switch
        $this->storeManager->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode());

        // Update address and customer data for quote
        $this->addressService->handle($pairing, $quote);

        // Convert to Order
        $orderId = $this->quoteManagement->placeOrder($quote->getId());
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        if (!$order->getEmailSent()) {
            $storeId = (int) $order->getStoreId();
            try {
                $this->emulate->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                $this->localeResolver->emulate($storeId);
                Phrase::setRenderer($this->phraseRenderer); // Fix for version 2.4.6
                $this->orderSender->send($order, true);
            } catch (Throwable $e) {
                $this->logger->error('[TWINT] Failed to send order confirmation email', [
                    'order_id' => $order->getIncrementId(),
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $this->emulate->stopEnvironmentEmulation();
                $this->localeResolver->revert();
            }
        }

        //Update Pairing and History
        $this->updateOrderIdForPairing($order, $quote->getId());

        // Remove items or original Quote
        $org = $this->quoteRepository->get($pairing->getOriginalQuoteId());
        $this->quoteService->removeAllItems($org);

        return $order->getIncrementId();
    }

    /**
     * @param Order $order
     */
    private function updateOrderIdForPairing(Order|OrderInterface $order, string|int $quoteId): void
    {
        $incrementId = $order->getIncrementId();

        $this->pairingService->appendOrderId($quoteId, $incrementId);
    }
}
