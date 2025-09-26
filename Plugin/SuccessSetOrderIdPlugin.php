<?php

declare(strict_types=1);

namespace Twint\Magento\Plugin;

use Magento\Checkout\Controller\Onepage\Success as SuccessController;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Allow passing order_id (increment_id) to the success page so it is treated as the last successful order.
 * The parameter value is the order Increment ID, not the entity_id.
 */
class SuccessSetOrderIdPlugin
{
    private RequestInterface $request;

    private CheckoutSession $checkoutSession;

    private OrderRepositoryInterface $orderRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private LoggerInterface $logger;

    public function __construct(
        RequestInterface $request,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Before plugin for Success::execute
     * If order_id (increment_id) param is present, set it to checkout session as last successful order identifiers.
     */
    public function beforeExecute(SuccessController $subject): void
    {
        $incrementId = trim((string) $this->request->getParam('twint_success_order'));
        if ($incrementId === '') {
            return; // nothing to do
        }

        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId, 'eq')
                ->setPageSize(1)
                ->create();
            $result = $this->orderRepository->getList($criteria);
            $items = $result->getItems();
            $order = $items ? reset($items) : null;

            if (!$order) {
                $this->logger->warning('[TWINT] No order found for provided increment_id on success page', [
                    'increment_id' => $incrementId,
                ]);
                return;
            }
        } catch (Throwable $e) {
            // Do not break success page if the provided id is invalid
            $this->logger->warning('[TWINT] Failed to load order by increment_id for success page', [
                'increment_id' => $incrementId,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // Set checkout session identifiers so the success page renders order info
        try {
            $this->checkoutSession->setLastOrderId((int) $order->getEntityId());
            $this->checkoutSession->setLastRealOrderId((string) $order->getIncrementId());
            if ($order->getQuoteId()) {
                $this->checkoutSession->setLastSuccessQuoteId((int) $order->getQuoteId());
                $this->checkoutSession->setLastQuoteId((int) $order->getQuoteId());
            }
        } catch (Throwable $e) {
            $this->logger->error('[TWINT] Failed to set last order data in session for success page', [
                'increment_id' => $incrementId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
