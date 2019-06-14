<?php

namespace Reach\Payment\Block\Adminhtml\Sales\Order\View;

/**
 * Backend order view block for Reach payment information
 *
 * @package Reach\Payment\Block\Adminhtml\Order\View
 */
class Info extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
    
        $this->_order = $registry->registry('current_order');
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->_order->getPayment();
    }

    public function getDhlQuoteDetail()
    {
        $detail = [];
        $breakdown = $this->_order->getDhlBreakdown();
        if ($breakdown && gettype($breakdown) == 'string') {
             $detail = json_decode($breakdown, true);
        }

        return  $detail;
    }
    
    public function formatPrice($price = 0.00)
    {
        return $this->_order->formatPrice($price);
    }
}
