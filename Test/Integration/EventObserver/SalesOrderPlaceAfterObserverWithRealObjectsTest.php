<?php


namespace Reach\Payment\Test\Integration\EventObserver;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Model\Info;
use Magento\TestFramework\ObjectManager;


// @magentoDbIsolation enabled -- temporarily removed it to be able to check
// what is in the database
// * @magentoAppIsolation enabled

//https://www.schmengler-se.de/en/2016/09/magento2-integration-tests-magentoconfigfixture/
//https://devdocs.magento.com/guides/v2.3/test/integration/annotations/magento-config-fixture.html
//https://devdocs.magento.com/guides/v2.3/graphql/functional-testing.html says
// "@magentoConfigFixture performs the following ... action as a background process before test execution:
// it inserts the field and value in core_config_data table"
// * @magentoConfigFixture default_store  reach/dhl/enable true
// * @magentoConfigFixture  default_store currency/options/allow USD



class SalesOrderPlaceAfterObserverTest extends \PHPUnit\Framework\TestCase
{

    //if we load fixtures this way then it can be anywhere instead of being in /dev/tests/...
    //folder
    public static function loadFixture()
    {
        //there can be multiple of this here
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

    //This config fixture below seems to be potentially unsafe.
    //Need to find a better way if there is one that would work with M2
    //(following the conventions that came with M2 integration testing framework
    //as is.)
    /**
     * @covers \Magento\Config\Model\Config::save
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store reach/global/active 1
     * @magentoConfigFixture default_store reach/global/merchant_id fake
     * @magentoConfigFixture default_store reach/global/api_secret fake
     * @magentoConfigFixture  default_store reach/global/display_currency_switch USD
     * @magentoConfigFixture  default_store reach/global/mode 1
     * @magentoDataFixture loadFixture
     */
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
        //does not return too soon. Also the event handler talks to api which ideally should be replaced
        //with mock in integration test and be tested seperately as web api test. The reason is:
        //seperate business logic testing from intermittent api failure issues
        //Right now did not leverage mock for the ap piece and the whole thing could not be
        //tested as is without prior steps (could be added as a fixture) of getting orderid, fingerprint
        //etc from api.
        //Just focusing/testing the concept of how magento config fixture works instead of doing it
        //perfectly at this stage/iteration
        $this->assertInstanceOf(\Magento\Payment\Model\InfoInterface::class  , $order->getPayment());
    }
}