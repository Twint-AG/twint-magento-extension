<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Twint\Magento\Api\RequestLogRepositoryInterface;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RequestLogRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function _isAllowed()
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
        $request = $this->repository->getById($id);

        if (!$request) {
            throw new LocalizedException(__("Request #{$id} not found"));
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()
            ->getTitle()
            ->prepend(__('Request Log') . ' #' . $id);

        // Pass the request data to the block
        /** @var \Twint\Magento\Block\Adminhtml\Request\View $block */
        $block = $resultPage->getLayout()
            ->getBlock('twint_request_view');
        $block?->setEntity($request);

        return $resultPage;
    }
}
