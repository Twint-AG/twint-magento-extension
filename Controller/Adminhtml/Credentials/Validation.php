<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Credentials;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Twint\Magento\Validator\CredentialValidator;
use Twint\Sdk\Value\Environment;

class Validation extends Action implements ActionInterface, HttpPostActionInterface
{
    protected JsonFactory $jsonFactory;

    protected Http $request;

    protected CredentialValidator $validator;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Http $request,
        CredentialValidator $validator,
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->validator = $validator;
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Twint_Magento::payment');
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $resultJson = $this->jsonFactory->create();
        $cert = $this->request->get('certificate') ?? [];
        $environment = (string) $this->request->get('environment') ?? Environment::TESTING;
        $storeUuid = $this->request->get('storeUuid') ?? '';

        try {
            $valid = $this->validator->validate($cert, $storeUuid, $environment);
            return $resultJson->setData([
                'success' => $valid,
                'message' => $valid ? '' : __('Invalid credentials. Please check again: Store UUID, certificate and environment (mode)'),
            ]);
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
