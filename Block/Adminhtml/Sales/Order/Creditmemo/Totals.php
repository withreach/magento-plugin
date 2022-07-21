<?php

namespace Reach\Payment\Block\Adminhtml\Sales\Order\Creditmemo;

class Totals extends \Magento\Framework\View\Element\Template
{
 /**
  * Order invoice
  *
  * @var \Magento\Sales\Model\Order\Creditmemo|null
  */
    protected $_creditmemo = null;
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;
    /**
     * OrderFee constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }
    /**
     * Initialize payment reach_duty totals
     *
     * @return $this
     */
    public function initTotals()
    {
        /** @var \Magento\Sales\Block\Order\Totals $parent */
        $parent = $this->getParentBlock();
        $source = $this->getSource();

        if (!$source->getReachDuty()) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject(
            [
                'code' => 'reach_duty',
                'value' => $source->getReachDuty(),
                'label' => 'Tax & Duties',
                'base_value' => $source->getBaseReachDuty()
            ]
        );

        $parent->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
