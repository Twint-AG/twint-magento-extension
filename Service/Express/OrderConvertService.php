<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Twint\Magento\Exception\PaymentException;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingHistory;
use Twint\Magento\Service\PairingService;

class OrderConvertService
{
    public function __construct(
        private CartManagementInterface $quoteManagement,
        private PairingService          $pairingService,
        private AddressService          $addressService,
        private QuoteService            $quoteService,
        private OrderRepository         $orderRepository,
        private QuoteRepository         $quoteRepository
    )
    {

    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function convert(Pairing $pairing, PairingHistory $history): ?string
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($pairing->getQuoteId());

        // Update address and customer data for quote
        $this->addressService->handle($pairing, $quote);

        // Convert to Order
        $orderId = $this->quoteManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);

        //Update Pairing and History
        $this->updateOrderIdForPairing($order, $quote->getId());

        // Remove items or original Quote
        $org = $this->quoteRepository->get($pairing->getOriginalQuoteId());
        $this->quoteService->removeAllItems($org);

        return $order->getIncrementId();
    }

    /**
     * @param Order $order
     * @param string|int $quoteId
     * @return void
     */
    private function updateOrderIdForPairing(Order|OrderInterface $order, string|int $quoteId): void
    {
        $incrementId = $order->getIncrementId();

        $this->pairingService->appendOrderId($quoteId, $incrementId);
    }
}
