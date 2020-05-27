<?php
namespace Reach\Payment\Test\Integration\Controller;


//credit goes to scommerce blog: tried their code to get a sense of how the
//code style/adjustment would be for testing Magento components
/**
 * @magentoAppIsolation enabled
 */
class ProductTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @magentoDataFixture Reach/Payment/Test/Integration/_files/products.php
     * @magentoAppArea frontend
     */
    public function testViewAction()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /**
         * @var $repository \Magento\Catalog\Model\ProductRepository
         */
        $repository = $objectManager->create('Magento\Catalog\Model\ProductRepository');
        $product = $repository->get('simple_product_2');
        $this->dispatch(sprintf('catalog/product/view/id/%s', $product->getEntityId()));

        /** @var $currentProduct \Magento\Catalog\Model\Product */
        $currentProduct = $objectManager->get('Magento\Framework\Registry')->registry('current_product');
        $this->assertInstanceOf('Magento\Catalog\Model\Product', $currentProduct);
        $this->assertEquals($product->getEntityId(), $currentProduct->getEntityId());

        $lastViewedProductId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Catalog\Model\Session'
        )->getLastViewedProductId();
        $this->assertEquals($product->getEntityId(), $lastViewedProductId);

        $responseBody = $this->getResponse()->getBody();
        print_r($responseBody);

        //will be used in future
        /* Product info */
        //$this->assertContains('Simple Product 1 Name', $responseBody);
       // $this->assertContains('Simple Product 1 Full Description', $responseBody);
        //$this->assertContains('Simple Product 1 Short Description', $responseBody);
        /* Stock info */
       // $this->assertContains('$1,234.56', $responseBody);
       // $this->assertContains('In stock', $responseBody);
        //$this->assertContains('Add to Cart', $responseBody);
        /* Meta info */
       // $this->assertContains('<title>Simple Product 1 Meta Title</title>', $responseBody);
       // $this->assertSelectCount('meta[name="keywords"][content="Simple Product 1 Meta Keyword"]', 1, $responseBody);
       // $this->assertSelectCount(
       //     'meta[name="description"][content="Simple Product 1 Meta Description"]',
       //     1,
       //     $responseBody
       // );
    }
}