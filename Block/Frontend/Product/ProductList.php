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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;
use Twint\Magento\Block\Frontend\Express\Screen\Category\Button;

class ProductList extends ProductsList
{
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
     * @return mixed|string|null
     */
    public function getModuleName(): mixed
    {
        if (!$this->_getData('module_name')) {
            $this->setData('module_name', self::extractModuleName(ProductsList::class));
        }
        return $this->_getData('module_name');
    }
}
