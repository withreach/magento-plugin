<?php


namespace Reach\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckoutCartIndex implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;
    
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $_quoteFactory;


    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
    
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_quoteFactory    = $quoteFactory;
    }

    /**
     * Checkout Cart index observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Framework\Event\Observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        $pendingPaymentOrderId = $this->_checkoutSession->getData("reach_order_pending_payment");

        if (!empty($pendingPaymentOrderId)) {
            $order = $this->_orderFactory->create()->load($pendingPaymentOrderId);
            if ($order !== null && $order->getId() !== null
                && $order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            ) {
                $quote = $this->_checkoutSession->getQuote();
                if (empty($quote) || empty($quote->getId())) {
                    $order->cancel()->save();
                    try {
                        $quote = $this->_quoteFactory->create()->load($order->getQuoteId());
                    } catch (\Exception $e) {
                    }
                    if ($quote->getId()) {
                        $quote->setIsActive(1);
                        $quote->setReservedOrderId(null);
                        $quote->save();
                        $this->_checkoutSession->replaceQuote($quote);
                    }
                    $this->_checkoutSession->setData("reach_order_pending_payment", null);
                }
            }
        }
        return $observer;
    }
}
