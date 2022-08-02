<?php

namespace Reach\Payment\Model;

use Reach\Payment\Api\Data\ContractInterface;

class Contract extends \Magento\Framework\Model\AbstractModel implements ContractInterface
{
    const CONTRACT_ID = 'contract_id';
    const CUSTOMER_ID = 'customer_id';
    const REACH_CONTRACT_ID = 'reach_contract_id';
    const CURRENCY = 'currency';
    const METHOD = 'method';
    const IDENTIFIER = 'identifier';
    const CREATED_AT = 'created_at';
    const EXPIRED_AT = 'expired_at';
    const CLOSED_AT = 'closed_at';

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context,
     * @param \Magento\Framework\Registry $registry,
     * @param \Reach\Payment\Model\ResourceModel\Contract $resource,
     * @param \Reach\Payment\Model\ResourceModel\Contract\Collection $collection,
     * @param array $data = []
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Reach\Payment\Model\ResourceModel\Contract $resource,
        \Reach\Payment\Model\ResourceModel\Contract\Collection $collection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $collection, $data);
    }

    /**
     * @inheirtDoc
     */
    public function getContractId()
    {
        return $this->_getData(self::CONTRACT_ID);
    }

    public function setContractId($contractId)
    {
        $this->setData(self::CONTRACT_ID, $contractId);
    }

    public function getCustomerId()
    {
        return $this->_getData(self::CUSTOMER_ID);
    }

    public function setCustomerId($customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getReachContractId()
    {
        return $this->_getData(self::REACH_CONTRACT_ID);
    }

    public function setReachContractId($contractId)
    {
        $this->setData(self::REACH_CONTRACT_ID, $contractId);
    }

    public function getCurrency()
    {
        return $this->_getData(self::CURRENCY);
    }

    public function setCurrency($currency)
    {
        $this->setData(self::CURRENCY, $currency);
    }

    public function getMethod()
    {
        return $this->_getData(self::METHOD);
    }

    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);
    }

    public function getIdentifier()
    {
        return $this->_getData(self::IDENTIFIER);
    }

    public function setIdentifier($identifier)
    {
        $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getExpiredAt()
    {
        return $this->_getData(self::EXPIRED_AT);
    }

    public function setExpiredAt($expiredAt)
    {
        $this->setData(self::EXPIRED_AT, $expiredAt);
    }

    public function getClosedAt()
    {
        return $this->_getData(self::CLOSED_AT);
    }

    public function setClosedAt($closedAt)
    {
        $this->setData(self::CLOSED_AT, $closedAt);
    }

    /**
     * Save open contract
     *
     * @param int $customerId
     * @param string $contractId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveCustomerContract($customerId, $contractId)
    {
        $this->setCustomerId($customerId);
        $this->setReachContractId($contractId);
        $this->save();
    }
}
