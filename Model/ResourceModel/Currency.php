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

    //should write  deletion routine too

    /**
     * To retrieve precision of a currency from the database.
     *
     * @param $currencyCode string
     * @return array
     */
    public function getPrecisionByCurrency($currencyCode)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('reach_currency_precision'))
            ->where('currency_code = ?', $currencyCode);
        return $connection->fetchAssoc($select);
    }

    /**
     * To save precision of a currency into the database.
     *
     * https://framework.zend.com/manual/1.12/en/zend.db.adapter.html says:
     * The return value of the insert() method is not the last inserted ID, because the table 
     * might not have an auto-incremented column. Instead, the return value is the number of 
     * rows affected (usually 1).
     * If your table is defined with an auto-incrementing primary key, you can call the lastInsertId()
     * method after the insert. This method returns the last value generated in the scope of the current
     * database connection.

     * @param $currencyCode string
     * @param $precision integer
     * @return \Zend_Db_Statement_Interface
     */
    public function setPrecisionByCurrency($currencyCode, $precision)
    {
        $connection = $this->getConnection();

        $connection->insert('reach_currency_precision', [
            'currency_code' => $currencyCode,
            'precision_unit' => $precision,
        ]);
    }


    /**
     * Delete unavailable rates
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
