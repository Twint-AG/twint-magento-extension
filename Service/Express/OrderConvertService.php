<?php

declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\App\Emulation;
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
        private Emulation $emulate
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function convert(Pairing $pairing, PairingHistory $history = null): ?string
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($pairing->getQuoteId());

        // Update address and customer data for quote
        $this->addressService->handle($pairing, $quote);

        // Convert to Order
        $orderId = $this->quoteManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);

        try {
            if (!$order->getEmailSent()) {
                $storeId = $order->getStoreId();
                // Emulate the store's environment to ensure the correct language is applied
                $this->emulate->startEnvironmentEmulation($storeId);
                $this->orderSender->send($order);
                // Stop the emulation after sending the email
                $this->emulate->stopEnvironmentEmulation();
            }
        } catch (Throwable $e) {
            //silence when error sending email
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
