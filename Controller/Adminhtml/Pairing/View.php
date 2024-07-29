<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Pairing;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Twint\Magento\Api\PairingRepositoryInterface;
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
     * @throws LocalizedException
     */
    public function execute(): Page|ResultInterface|ResponseInterface
    {
        $id = $this->getRequest()
            ->getParam('id');

        /** @var Pairing $pairing */
        $pairing = $this->repository->getById($id);
        if (!$pairing) {
            throw new LocalizedException(__('Pairing #%1 not found', $id));
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()
            ->getTitle()
            ->prepend(__('Order #%1 - Transaction #%2', $pairing->getOrderId(), $pairing->getId()));

        /** @var View $block */
        $block = $resultPage->getLayout()
            ->getBlock('pairing.view');
        $block?->setEntity($pairing);

        return $resultPage;
    }
}
