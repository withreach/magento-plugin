<?php
namespace Reach\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddDutyToOrderObserver implements ObserverInterface
{
    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $duty = $quote->getReachDuty();
        if (!$duty) {
            return $this;
        }
        
        $order = $observer->getOrder();

        $order->setData('reach_duty', $duty);
        
        $baseDuty = $quote->getBaseReachDuty();
        $order->setData('base_reach_duty', $baseDuty);

        $dhlQuoteId = $quote->getDhlQuoteId();
        if ($dhlQuoteId) {
            $order->setData('dhl_quote_id', $dhlQuoteId);
        }

        $dhlBreakdown = $quote->getDhlBreakdown();
        if ($dhlBreakdown) {
            $order->setData('dhl_breakdown', $dhlBreakdown);
        }


        return $this;
    }
}
