<?php


namespace Reach\Payment\Test\Integration\EventObserver;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Model\Info;
use Magento\TestFramework\ObjectManager;


// @magentoDbIsolation enabled -- temporarily removed it to be able to check
// what is in the database


//https://www.schmengler-se.de/en/2016/09/magento2-integration-tests-magentoconfigfixture/
//https://devdocs.magento.com/guides/v2.3/test/integration/annotations/magento-config-fixture.html
/**
 * @magentoAppIsolation enabled
 * @magentoConfigFixture store1_store reach/global/merchant_id 'fakemerchantid'
 * @magentoConfigFixture store1_store  'reach/dhl/enable' True
 * @magentoConfigFixture store1_store  currency/options/allow USD
 * @magentoDataFixture loadFixture

 */
class SalesOrderPlaceAfterObserverTest extends \PHPUnit\Framework\TestCase
{

    //if we load fixtures this way then it can be anywhere instead of being in /dev/tests/...
    //folder
    public static function loadFixture()
    {
        //include __DIR__ .'/../_files/customer.php';
        include __DIR__ .'/../_files/order_fee_variation.php';

    }

    /** @var string $event */
    private $event = "checkout_submit_all_after";



    /**
     * @var \Magento\TestFramework\ObjectManager $objectManager
     */
    private $objectManager;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    private $quoteFactory;

    /**
     * @var Magento\Checkout\Model\Session $checkoutSession
     */
    private $checkoutSession;

    /** @var  EventManager $eventManager */
    private $eventManager;


    public function testExecute()
    {

        $incrementId = '100000005';

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $repository = $this->objectManager->create('Magento\Sales\Api\OrderRepositoryInterface');

        //https://magento.stackexchange.com/a/204010
        $this->searchCriteriaBuilder = $this->objectManager->create('\Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();

        $orders = $repository->getList($searchCriteria)->getItems();
        $order = array_values($orders)[0];


        /** @var array $eventData */
        $this->eventData = [
            'order' => $order
        ];

        //Act
        //what to dispatch came from etc/events.xml
        $this->eventManager = ObjectManager::getInstance()->create(EventManager::class);
        $this->eventManager->dispatch($this->event, $this->eventData);

        //the test is passing but I have to be bit more picky; have to expand to make sure
        //right set of values are loaded in data fixtures such that the code flow
        //does not return too soon.
        $this->assertInstanceOf(\Magento\Payment\Model\InfoInterface::class  , $order->getPayment());
    }
}