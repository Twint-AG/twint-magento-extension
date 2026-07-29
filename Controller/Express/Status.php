<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Express;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Twint\Magento\Controller\Regular\BaseAction;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Model\PairingRepository;
use Twint\Magento\Service\MonitorService;
use Twint\Magento\Util\CryptoHandler;

class Status extends BaseAction implements ActionInterface, HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private readonly MonitorService $monitorService,
        private readonly CryptoHandler $cryptoHandler,
        private readonly PairingRepository $repository,
        private readonly CheckoutSession $checkoutSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * @throws NoSuchEntityException
     * @throws Throwable
     * @throws LocalizedException
     */
    public function execute()
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $step = 'init';
        try {
            $step = 'get_id';
            $id = $this->getRequest()
                ->getParam('id') ?? null;

            if (empty($id)) {
                throw new BadRequestHttpException('Pairing Id is required');
            }

            $step = 'decrypt_id';
            $id = $this->cryptoHandler->unHash($id);

            $step = 'load_pairing';
            $pairing = $this->repository->getByPairingId($id);
            if (!$pairing instanceof Pairing) {
                throw new NotFoundHttpException('Pairing not found');
            }

            $step = 'monitor_status';
            $monitorStatus = $this->monitorService->status($pairing);

            if ($monitorStatus->paid() && $incrementId = $monitorStatus->getAdditionalInformation('order')) {
                $step = 'set_success_order';
                $this->setSuccessOrder($incrementId);
            }

            $step = 'response';
            $data = [
                'finish' => $monitorStatus->getFinished(),
                'status' => $monitorStatus->getStatus(),
                'order' => $monitorStatus->getAdditionalInformation('order'),
                'errorMessage' => $monitorStatus->getAdditionalInformation('message'),
            ];
            if ($monitorStatus->paid()) {
                $data['successUrl'] = $this->storeManager->getStore(
                    $pairing->getStoreId()
                )->getBaseUrl() . 'checkout/onepage/success';
            }
            return $json->setData($data);
        } catch (HttpException $e) {
            return $json->setHttpResponseCode($e->getCode())->setData([
                'finish' => true,
                'status' => 'error',
                'errorMessage' => $e->getMessage(),
                'step' => $step,
            ]);
        } catch (Throwable $e) {
            $this->logger->error(
                "[TWINT] Express Status error: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}",
                [
                    'step' => $step,
                    'error' => $e->getMessage(),
                ]
            );

            return $json->setHttpResponseCode($e->getCode())->setData([
                'success' => false,
                'step' => $step,
            ]);
        }
    }

    protected function setSuccessOrder(string $incrementId): void
    {
        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->setPageSize(1)
                ->create();

            $items = $this->orderRepository->getList($criteria)->getItems();
            $order = $items ? reset($items) : null;

            if (!$order) {
                $this->logger->warning('[TWINT] No order found for provided increment_id on success page', [
                    'increment_id' => $incrementId,
                ]);
                return;
            }
        } catch (Throwable $e) {
            $this->logger->warning('[TWINT] Failed to load order by increment_id for success page', [
                'increment_id' => $incrementId,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        try {
            $this->checkoutSession->setLastOrderId((int) $order->getEntityId());
            $this->checkoutSession->setLastRealOrderId((string) $order->getIncrementId());
            if ($order->getQuoteId()) {
                $this->checkoutSession->setLastSuccessQuoteId((int) $order->getQuoteId());
                $this->checkoutSession->setLastQuoteId((int) $order->getQuoteId());
            }
        } catch (Throwable $e) {
            $this->logger->error('[TWINT] Failed to set last order data in session for success page', [
                'increment_id' => $incrementId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
