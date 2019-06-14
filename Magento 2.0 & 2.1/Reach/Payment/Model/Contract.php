<?php

namespace Reach\Payment\Model;

use Reach\Payment\Helper\Data as ReachHelper;

class Contract extends \Magento\Framework\Model\AbstractModel
{

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
