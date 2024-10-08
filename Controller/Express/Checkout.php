<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Express;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\Express\CheckoutService;
use Twint\Magento\Util\CryptoHandler;

class Checkout extends Add implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        protected CheckoutService $checkoutService,
        private readonly PriceHelper $priceHelper,
        private readonly Monolog $logger,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        private CryptoHandler $cryptoHandler,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        protected ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository, $quantityProcessor);
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $params = $this->getRequest()
            ->getParams();

        $product = $this->_initProduct();
        $request = new DataObject($params);

        try {
            $wholeCart = (bool) ($params['whole_cart'] ?? false);

            $items = $this->cart->getItems();
            $count = is_array($items) ? count($items) : $items->count();

            if (!$wholeCart && $count > 0) {
                $this->messageManager->addSuccessMessage(
                    __('You have existing products in the shopping cart. Please review your shopping cart before continue.')
                );

                return $json->setData([
                    'showMiniCart' => true,
                ]);
            }

            // Checkout in cart but don't have item
            if ($wholeCart && $count === 0) {
                return $json->setData([
                    'reload' => true,
                ]);
            }

            /** @var Pairing $pairing */
            $pairing = $this->checkoutService->checkout($product, $request);

            return $json->setData([
                'success' => true,
                'id' => $pairing->getId(),
                'pairingId' => $this->cryptoHandler->hash($pairing->getPairingId()),
                'token' => $pairing->getToken(),
                'amount' => $this->priceHelper->currency($pairing->getAmount(), true, false),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('TWINT EC error: ' . $e->getMessage());

            return $json->setData([
                'success' => false,
            ]);
        }
    }
}
