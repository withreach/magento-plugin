<?php

namespace Reach\Payment\Api\Data;

interface ContractInterface
{
    /**
     * @return int
     */
    public function getContractId();

    /**
     * @param int $contractId
     * @return void
     */
    public function setContractId($contractId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return void
     */
    public function setCustomerId($customerId);

    /**
     * @return string
     */
    public function getReachContractId();

    /**
     * @param string $contractId
     * @return void
     */
    public function setReachContractId($contractId);

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return void
     */
    public function setCurrency($currency);

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param string $method
     * @return void
     */
    public function setMethod($method);

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @param string $identifier
     * @return void
     */
    public function setIdentifier($identifier);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return mixed
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getExpiredAt();

    /**
     * @param string $expiredAt
     * @return mixed
     */
    public function setExpiredAt($expiredAt);

    /**
     * @return string
     */
    public function getClosedAt();

    /**
     * @param string $closedAt
     * @return mixed
     */
    public function setClosedAt($closedAt);
}
