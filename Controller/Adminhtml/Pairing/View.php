<?php

namespace Twint\Magento\Controller\Adminhtml\Pairing;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Twint\Magento\Model\PairingFactory;
class View extends Action{
    public function __construct(
        Context $context,
        private  readonly PageFactory $resultPageFactory,
        private  readonly PairingFactory $pairingFactory
    )
    {
        parent::__construct($context);
    }

    public function execute(): Page|ResultInterface|ResponseInterface
    {
        $id = $this->getRequest()->getParam('id');
        $pairing = $this->pairingFactory->create()->load($id);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Transaction history'));

        return $resultPage;
    }
}
