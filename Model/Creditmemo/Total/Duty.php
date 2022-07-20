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
        $reachDuty = $creditmemo->getOrder()->getReachDuty();
        $baseReachDuty = $creditmemo->getOrder()->getBaseReachDuty();
        $creditmemo->setReachDuty($reachDuty);
        $creditmemo->setBaseReachDuty($baseReachDuty);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $reachDuty);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseReachDuty);

        return $this;
    }
}
