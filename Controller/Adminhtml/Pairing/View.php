<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Pairing;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Twint\Magento\Api\PairingRepositoryInterface;
use Twint\Magento\Block\Adminhtml\Pairing\View as PairingView;
use Twint\Magento\Model\Pairing;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly PairingRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions');
    }

    /**
     * @return Page|ResultInterface|ResponseInterface
     * @throws Exception
     */
    public function execute(): Page|ResultInterface|ResponseInterface
    {
        $id = $this->getRequest()
            ->getParam('id');

        /** @var Pairing $pairing */
        $pairing = $this->repository->getById($id);
        if (!$pairing) {
            throw new Exception("Pairing $id not found");
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()
            ->getTitle()
            ->prepend(__('Order') . $pairing->getOrderId() . ' - ' . __('Transaction') . ' ' . $pairing->getId());

        /** @var PairingView $block */
        $block = $resultPage->getLayout()
            ->getBlock('pairing.view');
        $block?->setEntity($pairing);

        return $resultPage;
    }
}
