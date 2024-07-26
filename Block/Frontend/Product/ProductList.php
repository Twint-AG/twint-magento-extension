<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\CatalogWidget\Model\Rule;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;
use Twint\Magento\Block\Frontend\Express\Widget\ProductList\Button;

class ProductList extends ProductsList
{
    const TEMPLATE_DEFAULT = [
        'product/widget/content/grid.phtml',
        'Magento_CatalogWidget::product/widget/content/grid.phtml'
    ];

    const TEMPLATE_EXPRESS = 'Twint_Magento::widget/product/express.phtml';

    public function __construct(
        public readonly Button      $expressButton,
        Context                     $context,
        CollectionFactory           $productCollectionFactory,
        Visibility                  $catalogProductVisibility,
        HttpContext                 $httpContext,
        SqlBuilder                  $sqlBuilder,
        Rule                        $rule, Conditions $conditionsHelper,
        array                       $data = [],
        Json                        $json = null,
        LayoutFactory               $layoutFactory = null,
        EncoderInterface            $urlEncoder = null,
        CategoryRepositoryInterface $categoryRepository = null)
    {
        parent::__construct($context, $productCollectionFactory, $catalogProductVisibility, $httpContext, $sqlBuilder, $rule, $conditionsHelper, $data, $json, $layoutFactory, $urlEncoder, $categoryRepository);
    }

    /**
     * Override this method from base class to allow Magento detect view file of template
     *
     * @return mixed
     */
    public function getModuleName()
    {
        if (!$this->_getData('module_name')) {
            $this->setData('module_name', self::extractModuleName(ProductsList::class));
        }
        return $this->_getData('module_name');
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getTemplate()
    {
        if($this->expressButton->shouldRender() && in_array($this->_template, self::TEMPLATE_DEFAULT)){
            $this->_template = self::TEMPLATE_EXPRESS;
        }

        return parent::getTemplate();
    }
}
