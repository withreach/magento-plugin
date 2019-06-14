<?php

namespace Reach\Payment\Model\ResourceModel;

class Contract extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context,
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
         parent::__construct($context, $connectionName);
    }

    /**
     * Define Main Table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('reach_open_contract', 'contract_id');
    }
}
