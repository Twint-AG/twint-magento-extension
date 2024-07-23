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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Twint\Magento\Model\Pairing;
use Twint\Magento\Service\Express\CheckoutService;

class Checkout extends Add implements ActionInterface, HttpPostActionInterface
{
    public function __construct(
        protected CheckoutService           $checkoutService,
        private readonly PriceHelper        $priceHelper,
        Context                             $context,
        private ScopeConfigInterface        $scopeConfig,
        private Session                     $checkoutSession,
        private StoreManagerInterface       $storeManager,
        private Validator                   $formKeyValidator,
        Cart                                $cart,
        ProductRepositoryInterface          $productRepository,
        protected ?RequestQuantityProcessor $quantityProcessor = null
    )
    {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository, $quantityProcessor);

        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
    }

    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $params = $this->getRequest()->getParams();

        $wholeCart = (bool)($params['whole_cart'] ?? false);
        $items = $this->cart->getItems();
        $count = is_array($items) ? count($items) : $items->count();

        if ($count > 0 && !$wholeCart) {
            $result = $this->parentCall();
            if (array_keys($result) == ['backUrl'])
                return $json->setData($result);

            $this->messageManager->addSuccessMessage(__("You have existing products in the shopping cart. Please review your shopping cart before continue."));

            return $json->setData(array_merge($result, [
                'showMiniCart' => true
            ]));
        }

        try {
            if (!$wholeCart) {
//                if (isset($params['qty'])) {
//                    $filter = new LocalizedToNormalized(
//                        ['locale' => $this->_objectManager->get(
//                            ResolverInterface::class
//                        )->getLocale()]
//                    );
//                    $params['qty'] = $this->quantityProcessor->prepareQuantity($params['qty']);
//                    $params['qty'] = $filter->filter($params['qty']);
//                }
//
//                $product = $this->_initProduct();
//                /** Check product availability */
//                if (!$product) {
//                    $this->messageManager->addErrorMessage(__("The product is not available"));
//                    return $json->setData([
//                        'success' => false
//                    ]);
//                }
//
//                $this->cart->addProduct($product, $params);
//
//                $related = $this->getRequest()->getParam('related_product');
//                if (!empty($related)) {
//                    $this->cart->addProductsByIds(explode(',', $related));
//                }

                $result = parent::execute();
                dd($result);
            }

            /** @var Pairing $pairing */
            $pairing = $this->checkoutService->checkout();


            return $json->setData([
                'success' => true,
                'id' => $pairing->getId(),
                'token' => $pairing->getToken(),
                'amount' => $this->priceHelper->currency($pairing->getAmount(), true, false),
            ]);
        } catch (\Throwable $e) {
            $this->_objectManager->get(LoggerInterface::class)->critical($e);

            return $json->setData([
                'success' => false
            ]);
        }
    }

    public function parentCall()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return [
                'reload' => true
            ];
        }

        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(ResolverInterface::class)->getLocale()]
                );
                $params['qty'] = $this->quantityProcessor->prepareQuantity($params['qty']);
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }

        return [];
    }

    protected function goBack($backUrl = null, $product = null): array
    {
        if ($backUrl) {
            return [
                'backUrl' => $backUrl
            ];
        }

        return [
            'reload' => true
        ];
    }
}
