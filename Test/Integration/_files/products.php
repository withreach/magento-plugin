<?php
//credit goes to scommerce blog and content
use Magento\TestFramework\Helper\Bootstrap;

/** @var $product \Magento\Catalog\Model\Product */
$product = Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Model\Product::class);
$product
    ->setTypeId('simple')
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple_product_1')  //simple product ; use this sku while testing and assertion
    ->setPrice(10)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->save();
/* //to be used in near future
$customDesignProduct = Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Model\Product::class, ['data' => $product->getData()]);

$customDesignProduct->setUrlKey('custom-design-simple-product')
    ->setId(2)
    ->setRowId(2)
    ->setSku('custom-design-simple-product')  //'24-UG01'
    ->setCustomDesign('Magento/blank')
    ->save();

*/