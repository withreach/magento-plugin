<?php


namespace Reach\Payment\Test\Integration\EventObserver;


use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\TestFramework\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Reach\Payment\Observer\AddDutyToOrderObserver;

//used concept from here http://vinaikopp.com/2016/07/08/13_the_event_observer_kata/

class AddDutyToOrderObserverTest extends \PHPUnit\Framework\TestCase
{
    private function dispatchEvent($event, array $eventData)
    {
        /** @var EventManager $eventManager */
        $eventManager = ObjectManager::getInstance()->create(EventManager::class);
        $eventManager->dispatch($event, $eventData);
    }

    public function testAddDutyToOrder()
    {
        //Arrange
        $quote = ObjectManager::getInstance()->create(Quote::class);
        $order = ObjectManager::getInstance()->create(Order::class);
        //duty is set on quote
        $quote->setData('reach_duty', 50);


        //Act
        //what to dispatch came from etc/events.xml
        $this->dispatchEvent(
            "sales_model_service_quote_submit_before",
            ['quote' => $quote, 'order' => $order]
        );


        //Assert
        //if the dispatched event is handled properly then duty data if there is any in the quote;
        //that would be copied to order
        //checking whether duty is set on/copied over to order
        $this->assertSame(50, $order->getData('reach_duty'));
    }

}