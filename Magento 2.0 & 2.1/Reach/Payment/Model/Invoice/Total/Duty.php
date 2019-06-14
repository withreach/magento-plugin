<?php

namespace Reach\Payment\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Duty extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoice->setReachDuty(0);
        $amount = $invoice->getOrder()->getReachDuty();
        $invoice->setReachDuty($amount);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
        return $this;
    }
}
