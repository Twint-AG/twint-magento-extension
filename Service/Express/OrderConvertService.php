<?php
declare(strict_types=1);

namespace Twint\Magento\Service\Express;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Sales\Model\OrderRepository;
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
        private QuoteRepository         $quoteRepository,
    )
    {

    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function convert(Pairing $pairing, PairingHistory $history)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($pairing->getQuoteId());

        // Update address and customer data for quote
        $this->addressService->handle($pairing, $quote);

        // Convert to Order
        $orderId = $this->quoteManagement->placeOrder($quote->getId());

        //Update Pairing and History
        $this->updateOrderIdForPairing($orderId, $quote->getId());

        // Remove items or original Quote
        $org = $this->quoteRepository->get($pairing->getOriginalQuoteId());
        $this->quoteService->removeAllItems($org);

        return $orderId;
    }

    /**
     * @throws NoSuchEntityException
     * @throws InputException
     */
    private function updateOrderIdForPairing(int $orderId, string|int $quoteId): void
    {
        $order = $this->orderRepository->get($orderId);
        $incrementId = $order->getIncrementId();

        $this->pairingService->appendOrderId($quoteId, $incrementId);
    }
}
