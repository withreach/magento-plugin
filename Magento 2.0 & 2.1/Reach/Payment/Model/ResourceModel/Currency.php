<?php

namespace Reach\Payment\Model\ResourceModel;

class Currency extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init('reach_currency_rate', 'rate_id');
    }

    public function getByCurrency($code)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('currency = ?', $code);
        return $connection->fetchAll($select);
    }

    /**
     * Delete not available rates
     *
     * @return void
     */
    public function removeOldRates($receviedRates)
    {
        if (!count($receviedRates)) {
            return;
        }
        $connection = $this->getConnection();
        $sql = "DELETE from ".$this->getMainTable()." where currency not in ('".implode("','", $receviedRates)."')";
        $connection->query($sql);
    }
}
