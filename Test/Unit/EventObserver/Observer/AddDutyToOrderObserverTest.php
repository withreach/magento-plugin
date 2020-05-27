<?php


namespace Reach\Payment\Test\Unit\EventObserver\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Reach\Payment\Observer\AddDutyToOrderObserver;
use Magento\Framework\Event\Observer;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager; //see instead of using regular ObjectManager
                                                           //we are using one from the test framework


class AddDutyToOrderObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Got guidance/hints from http://vinaikopp.com/2016/07/08/13_the_event_observer_kata/
     */

    protected function setUp()
    {
        $this->duty = 12.51;

        $this->mockQuote = $this->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods(['getReachDuty', 'getBaseReachDuty','setReachDuty','setBaseReachDuty'])
            ->getMock();
        $this->mockQuote->method('setReachDuty')->with($this->duty);
        $this->mockQuote->method('setBaseReachDuty')->with($this->duty);
        $this->mockQuote->method('getReachDuty')->willReturn($this->duty);
        $this->mockQuote->method('getBaseReachDuty')->willReturn($this->duty);

        $this->mockOrder = $this->getMockBuilder('Magento\Sales\Model\Order')
           ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods(['setData','getData'])
            ->getMock();

        $this->dhlQuoteId = 'A12345678';

        //https://stackoverflow.com/questions/5988616/phpunit-mock-method-multiple-calls-with-different-arguments
        //when single method name like `setData` is getting used to fill several property values
        //this is the style to use; otherwise the code fails

        //this would be leveraged when as for example $order->setData('reach_duty', $duty);
        //is getting called in AddDutyToObserver.php
        $this->mockOrder->method('setData')
            ->willReturnMap(
            [
                ['reach_duty', $this->duty],
                ['base_reach_duty',$this->duty],
                ['dhl_quote_id', $this->dhlQuoteId]
            ]
        );

        //Used hints from http://vinaikopp.com/2016/07/08/13_the_event_observer_kata/ regarding
        //how the getData method is set for mockEvent (in the function
        //testSetsTheMagentoSEPointsOnTheQuoteItem() ).
        //When single method name like `getData` is getting used to retrieve several property values
        //this is the style to use; otherwise the code fails
        $this->mockOrder->method('getData')
            ->willReturnMap(
                [
                    ['reach_duty', null,  $this->duty],
                    ['base_reach_duty', null, $this->duty]
                ]
            );

        $this->mockObserver = $this->getMockBuilder('Magento\Framework\Event\Observer')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods(['getQuote', 'getOrder','setQuote','setOrder'])
            ->getMock();

        //No confusion here for a field we have seperate method
        //So we are not using `willReturnMap` the way we did it for getData, setData
        $this->mockObserver->method('setQuote')->with($this->mockQuote);
        $this->mockObserver->method('getQuote')->willReturn($this->mockQuote);
        $this->mockObserver->method('setOrder')->with($this->mockOrder);
        $this->mockObserver->method('getOrder')->willReturn($this->mockOrder);

    }


    public function testImplementsTheEventObserverInterface()
    {
        $this->assertInstanceOf(ObserverInterface::class, new AddDutyToOrderObserver());
    }


    public function testExecute()
    {
        $objectManager = new ObjectManager($this);
        $this->addDutyToOrderObserver = $objectManager->getObject('Reach\Payment\Observer\AddDutyToOrderObserver');

        //trying to execute the `execute` method of the observer with mock data
        $this->addDutyToOrderObserver->execute($this->mockObserver);

        $this->assertEquals($this->mockObserver->getQuote(), $this->mockQuote);
        $this->assertEquals($this->mockObserver->getQuote()->getReachDuty(), $this->duty);
        $this->assertEquals($this->mockObserver->getOrder(), $this->mockOrder);

        //For the following assertion to work we had to do the following setup
        /**
         *  $this->mockOrder->method('getData')->willReturnMap(...)
         */
        $this->assertEquals($this->mockObserver->getOrder()->getData('reach_duty'),  $this->duty);
    }
}