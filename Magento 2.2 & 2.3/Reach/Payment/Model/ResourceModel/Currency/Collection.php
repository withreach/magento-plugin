<?php

namespace Reach\Payment\Model\ResourceModel\Currency;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName='rate_id';
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Reach\Payment\Model\Currency::class, \Reach\Payment\Model\ResourceModel\Currency::class);
    }
}
