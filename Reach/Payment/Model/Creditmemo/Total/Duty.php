<?php

namespace Reach\Payment\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Duty extends AbstractTotal
{

      /**
       * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
       * @return $this
       */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setReachDuty(0);
        $amount = $creditmemo->getOrder()->getReachDuty();
        $creditmemo->setReachDuty($amount);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
        return $this;
    }
}
