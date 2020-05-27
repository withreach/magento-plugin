<?php

//How to run:
//from command prompt and running from the parent directory of app/...
//php7.3 vendor/phpunit/phpunit/phpunit -c /opt/magento/public_html/dev/tests/integration/phpunit.xml app/code/Reach/Payment/Test/Integration/Controller/CallbackTest.php  --debug

class CallbackTest extends \Magento\TestFramework\TestCase\AbstractController
{
    //@magentoDbIsolation disabled : to be able to check the value in db; read about db isolation here
    // http://dusanlukic.com/annotations-in-magento-2-integration-tests : it is interesting
    //on the other hand @magentoAppIsolation
    //When this annotation is enabled, the application will be restarted (i.e. re- instantiate almost everything ;
    //would be useful as for example while dealing with singleton ) on each test run.
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Reach/Payment/Test/Integration/_files/order_express.php
     * @magentoAppArea frontend

     */
    public function testExecute()
    {   //https://stackoverflow.com/a/7493389
        //https://stackoverflow.com/a/16652301
        //$this->expectOutputString(''); // tell PHPUnit to expect '' as output

        //tables involved `sales_order` `sales_order_address`
        /**
         * @var $objectManager \Magento\TestFramework\ObjectManager
         */

        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        /**
         * @var $repository Magento\Sales\Api\OrderRepositoryInterface
         */

        $repository = $objectManager->create('Magento\Sales\Api\OrderRepositoryInterface');

        //https://magento.stackexchange.com/a/204010
        /**
         * @var $searchCriteriaBuilder \Magento\Framework\Api\SearchCriteriaBuilder
         */

        $incrementId = '100000002';
        $searchCriteriaBuilder = $objectManager->create('\Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();


        $orders = $repository->getList($searchCriteria)->getItems();
        $order = array_values($orders)[0];

        print_r($order->getBillingAddress()->getFirstname() );

        $result = $this->getResponse()->getReasonPhrase();
        var_dump($result);

        $this->assertInstanceOf('\Magento\Sales\Api\Data\OrderInterface', $order);
        $this->assertEquals('co1@co1.co',  $order->getCustomerEmail());
        $this->assertEquals('San Diego',  $order->getBillingAddress()->getCity());

        /**
         * @var $orderFactory \Magento\Sales\Model\OrderFactory
         */
        $orderFactory = $objectManager->create('\Magento\Sales\Model\OrderFactory');
        $orderRetrievedDifferently = $orderFactory->create()->loadByIncrementId($incrementId);
        $this->assertEquals($orderRetrievedDifferently->getBillingAddress()->getCity(), $order->getBillingAddress()->getCity());
    }
}