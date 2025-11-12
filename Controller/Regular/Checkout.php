<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Regular;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Twint\Magento\Model\Method\TwintRegularMethod;

class Checkout extends Action implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private Session $session,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $step = 'init';
        try {
            $step = 'get_order';
            $order = $this->session->getLastRealOrder();
            if (!$order) {
                throw new Exception('Dont have needed order to pay');
            }

            $step = 'validate_payment';
            $payment = $order->getPayment();
            if (!$payment || $payment->getMethod() !== TwintRegularMethod::CODE) {
                throw new Exception('This order did not processed by TWINT');
            }

            $step = 'build_response';
            $data = [
                'token' => $payment->getAdditionalInformation()['qrToken'],
                'orderNumber' => $order->getIncrementId(),
                'storeName' => $this->storeManager->getStore()
                    ->getName(),
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
}
