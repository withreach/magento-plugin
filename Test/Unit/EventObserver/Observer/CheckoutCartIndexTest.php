<?php


namespace Reach\Payment\Test\Unit\EventObserver\Observer;

use Reach\Payment\Observer\CheckoutCartIndex;
use \Magento\Framework\Event\Observer;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Quote\Model\QuoteFactory;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 *  Unit tests for CheckoutCartIndex class
 */
class CheckoutCartIndexTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {

        $this->pendingPaymentOrderId = 10;
        $this->quoteId = 1;
        $this->orderId = 1;

        $this->mockCheckoutSession = $this->getMockBuilder('\Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'setData','replaceQuote', 'getData'])
            ->getMock();

       $this->mockCheckoutSession->method('setData')->with('reach_order_pending_payment', null)
          ->willReturn(null);

        $this->mockCheckoutSession->expects($this->atLeastOnce())->method('getData')
            ->with('reach_order_pending_payment')
            ->willReturn($this->pendingPaymentOrderId);

        $this->mockQuote = $this->createMock('Magento\Quote\Model\Quote');


        $this->mockQuote->expects($this->atMost(1))->method('getId')->willReturn( $this->quoteId);

        $this->mockQuote->method('setIsActive')->with(1);


        $this->mockQuote->method('setReservedOrderId')->with(null);

        $this->mockQuoteFactory =  $this->createMock('\Magento\Quote\Model\QuoteFactory');

        $this->mockCheckoutSession->method('replaceQuote')->with($this->mockQuote);

        $this->mockOrder = $this->createMock('Magento\Sales\Model\Order');
        $this->mockOrderFactory =  $this->createMock('\Magento\Sales\Model\OrderFactory');


        $this->mockOrder->expects($this->once())->method('getState')->willReturn(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $this->mockOrder->method('setState')->with(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);

        $this->mockOrder->expects($this->atMost(1))->method('cancel')->willReturnSelf();
        $this->mockOrder->expects($this->atMost(1))->method('save')->willReturnSelf();
        $this->mockOrder->expects($this->Once())->method('getId')->willReturn($this->orderId);
        $this->mockOrder->method('setId')->with($this->orderId);

        $this->mockCheckoutSession->method('replaceQuote')->with($this->mockQuote);

        $this->mockObserver = $this->createMock('\Magento\Framework\Event\Observer');


    }

    public function testExecuteWithNonEmptyQuote()
    {

        $this->mockCheckoutSession->expects($this->atLeastOnce())->method('getData')->willReturnMap(
            [
                ['reach_order_pending_payment', $this->pendingPaymentOrderId]
            ]
        );



        $this->mockOrderFactory->expects($this->once())->method('create')->willReturn($this->mockOrder);
        $this->mockOrder->expects($this->once())->method('load')
            ->with($this->pendingPaymentOrderId)
            ->willReturn($this->mockOrder);

        $this->mockCheckoutSession->expects($this->exactly(1))->method('getQuote')->willReturn($this->mockQuote);

        $this->mockQuote->expects($this->never())->method('save')->willReturn(1); //some random return - we are not
        // using the return value anyway


        $objectManager = new ObjectManager($this);

        $this->checkoutCartIndex = $objectManager->getObject("Reach\Payment\Observer\CheckoutCartIndex", [
            'checkoutSession' => $this->mockCheckoutSession,
            'orderFactory' => $this->mockOrderFactory,
            'quoteFactory' => $this->mockQuoteFactory
        ]);

        //basically the call below is checking whether particular code pathways in CheckoutCartIndex.php are or are not
        // exercised (by using expects(...) constraints in test doubles)
        $this->checkoutCartIndex->execute($this->mockObserver);

   }


    public function testExecuteWithEmptyQuote()
    {

        $this->mockCheckoutSession->expects($this->atLeastOnce())->method('getData')->willReturnMap(
            [
                ['reach_order_pending_payment', $this->pendingPaymentOrderId]
            ]
        );

        $this->mockOrderFactory->expects($this->once())->method('create')->willReturn($this->mockOrder);
        $this->mockOrder->expects($this->once())->method('load')->with($this->pendingPaymentOrderId)->willReturn($this->mockOrder);
        $this->mockOrder->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($this->quoteId);

        $this->mockQuote_empty = null;

        $this->mockCheckoutSession->expects($this->once())->method('getQuote')->willReturn($this->mockQuote_empty);


        $this->mockOrder->expects($this->exactly(1))->method('cancel')->willReturnSelf();
        $this->mockOrder->expects($this->exactly(1))->method('save')->willReturnSelf();



        $this->mockQuoteFactory->expects($this->once())->method('create')->willReturn($this->mockQuote);

        $this->mockQuote->expects($this->once())->method('load')->with($this->mockOrder->getQuoteId())->willReturnSelf();
        $this->mockQuote->expects($this->once())->method('getId')->willReturn($this->orderId);



        $this->assertNotNull($this->mockCheckoutSession->getData('reach_order_pending_payment'));

        $objectManager = new ObjectManager($this);

        $this->checkoutCartIndex = $objectManager->getObject("Reach\Payment\Observer\CheckoutCartIndex", [
            'checkoutSession' => $this->mockCheckoutSession,
            'orderFactory' => $this->mockOrderFactory,
            'quoteFactory' => $this->mockQuoteFactory
        ]);

        //basically the call below is checking whether particular code pathways in CheckoutCartIndex.php are or are not
        // exercised (by using expects(...) constraints in test doubles)
        //doing extra check that it is returning non null observer (this check is not absolutely essential)
        $this->assertNotNull($this->checkoutCartIndex->execute($this->mockObserver));
    }

}