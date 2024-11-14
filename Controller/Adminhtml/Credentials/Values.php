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
use Twint\Magento\Helper\ConfigHelper;
use Twint\Magento\Validator\Input\ScopeInputValidator;

class Values extends Action implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        Action\Context              $context,
        private JsonFactory         $jsonFactory,
        private Http                $request,
        private ConfigHelper        $helper,
        private ScopeInputValidator $inputValidator,
    ) {
        parent::__construct($context);
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Twint_Magento::payment');
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->jsonFactory->create();
        $scope = $this->request->get('scope') ?? '';

        $errors = $this->inputValidator->validate($scope);
        if ($errors !== []) {
            return $json->setData([
                'success' => false,
                'message' => __('Invalid input'),
                'errors' => $errors,
            ]);
        }

        try {
            $credentials = $this->helper->getCredentials($scope);
            return $json->setData($credentials);
        } catch (Exception $e) {
            return $json->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
