<?php


namespace Reach\Payment\Test\Integration\EventObserver;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\TestFramework\ObjectManager;
use Magento\Checkout\Model\Session;
/**
 * @magentoAppIsolation enabled
 * @magentoDataFixture Reach/Payment/Test/Integration/_files/order_quote_pending.php
 * @magentoAppArea frontend
 */

class CheckoutCartIndexTest extends \PHPUnit\Framework\TestCase
{
    /** @var string $event */
    private $event = "controller_action_predispatch_checkout_cart_index";

    /** @var array $eventData */
    private $eventData = [];

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


    public function testExecuteCheckoutCartIndexHandler()
    {
        //Arrange
        $incrementId = '100000005';

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $repository = $this->objectManager->create('Magento\Sales\Api\OrderRepositoryInterface');

        //https://magento.stackexchange.com/a/204010
        $this->searchCriteriaBuilder = $this->objectManager->create('\Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();

        $orders = $repository->getList($searchCriteria)->getItems();

        $order = array_values($orders)[0];

        //Assert
        $this->assertInstanceOf('\Magento\Sales\Api\Data\OrderInterface', $order);


        $this->quoteFactory = $this->objectManager->create('Magento\Quote\Model\Quote');
        $quote = $this->quoteFactory->load($order->getQuoteId());


        $this->checkoutSession = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Session::class);

        $this->checkoutSession->setQuoteId($quote->getId());
        $this->checkoutSession->setQuote($quote);
        $this->checkoutSession->setData("reach_order_pending_payment", $order->getId());

        //Act
        //what to dispatch came from etc/events.xml
        $this->eventManager = ObjectManager::getInstance()->create(EventManager::class);
        $this->eventManager->dispatch($this->event, $this->eventData);

        //Assert
        $this->assertNull($this->checkoutSession->getData("reach_order_pending_payment"));

    }





}