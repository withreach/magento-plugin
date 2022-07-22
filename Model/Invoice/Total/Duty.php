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
        $reachDuty = $invoice->getOrder()->getReachDuty();
        $baseReachDuty = $invoice->getOrder()->getBaseReachDuty();
        $invoice->setReachDuty($reachDuty);
        $invoice->setBaseReachDuty($baseReachDuty);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $reachDuty);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseReachDuty);
        return $this;
    }
}
