<?php

namespace Reach\Payment\Model\ResourceModel\Contract;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName='contract_id';
    
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Reach\Payment\Model\Contract::class, \Reach\Payment\Model\ResourceModel\Contract::class);
    }
}
