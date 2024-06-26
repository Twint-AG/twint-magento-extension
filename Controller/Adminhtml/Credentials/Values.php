<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Credentials;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Twint\Magento\Helper\ConfigHelper;

class Values extends Action implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        Action\Context $context,
        private JsonFactory $jsonFactory,
        private Http $request,
        private ConfigHelper $helper,
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $scope = $this->request->get('scope') ?? '';

        try {
            $credentials = $this->helper->getCredentials($scope);
            return $resultJson->setData($credentials);
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
