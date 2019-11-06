<?php

namespace Reach\Payment\Model\ResourceModel;

class CsvHsCode extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init('reach_hs_code', 'id');
    }

    public function getHsCode($sku)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable());
        $select->where('sku = ?', $sku);
        $result = $connection->fetchRow($select);
        if (count($result) && isset($result['hs_code'])) {
            return $result['hs_code'];
        }
        return null;
    }
}
