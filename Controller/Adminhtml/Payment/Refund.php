<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Payment;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Twint\Magento\Service\RefundService;

class Refund extends Action implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly RefundService $service,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $json = $this->jsonFactory->create();

        $pairing = $this->getRequest()
            ->get('pairing_id') ?? 0;
        $amount = $this->getRequest()
            ->get('amount') ?? 0;
        $reason = $this->getRequest()
            ->get('reason') ?? 0;

        try {
            $success = $this->service->refund($pairing, $amount, $reason);
            return $json->setData([
                'success' => $success,
            ]);
        } catch (Exception $e) {
            return $json->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
