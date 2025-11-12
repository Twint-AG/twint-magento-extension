<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Regular;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Block\Frontend\ScanQrModal;
use Twint\Magento\Model\Method\TwintRegularMethod;
use Twint\Magento\Util\CryptoHandler;

class Payment extends BaseAction implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private Session $session,
        private OrderRepositoryInterface $orderRepository,
        private readonly PairingRepositoryInterface $repository,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly PriceHelper $priceHelper,
        private readonly CryptoHandler $cryptoHandler,
    ) {
        parent::__construct($context);
    }

    /**
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $step = 'init';
        try {
            $step = 'get_post_data';
            $data = $this->getPostData();
            $orderId = (int) ($data['order'] ?? 0);

            $step = 'get_order';
            $order = $this->getOrder($orderId);

            $step = 'validate_payment';
            $payment = $order->getPayment();
            if (!$payment || $payment->getMethod() !== TwintRegularMethod::CODE) {
                throw new Exception('This order did not processed by TWINT');
            }

            $step = 'get_pairing';
            $pairing = $this->getPairing($order);

            /** @var ScanQrModal $block */
            $step = 'render_modal';
            $block = $this->_view->getLayout()->createBlock(ScanQrModal::class);
            $block->setTemplate('Twint_Magento::qr.phtml');

            $step = 'response';
            $data = [
                'id' => $this->cryptoHandler->hash($pairing['pairing_id']),
                'token' => $pairing['token'],
                'orderNumber' => $order->getIncrementId(),
                'amount' => $this->priceHelper->currency($order->getBaseGrandTotal(), true, false),
                'modal' => $block->toHtml(),
            ];

            return $json->setData($data);
        } catch (Exception $e) {
            return $json->setData([
                'success' => false,
                'errorMessage' => $e->getMessage(),
                'step' => $step,
            ]);
        }
    }

    /**
     * @throws LocalizedException
     * @throws Exception
     */
    protected function getOrder(int $orderId): Order
    {
        $order = $orderId === 0 ? $this->session->getLastRealOrder() : $this->orderRepository->get($orderId);

        if ((!$order instanceof Order)) {
            throw new Exception("Order {$orderId} not found");
        }

        return $order;
    }

    /**
     * @throws LocalizedException
     */
    protected function getPairing(Order $order)
    {
        $criteria = $this->criteriaBuilder->addFilter('order_id', $order->getIncrementId())
            ->create();
        $pairings = $this->repository->getList($criteria)
            ->getItems();

        if (!empty($pairings)) {
            return reset($pairings); // Return the first item
        }

        throw new LocalizedException(__("Cannot find pairing item for order #{$order->getIncrementId()}"));
    }
}
