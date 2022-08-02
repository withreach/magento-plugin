<?php

namespace Reach\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Reach\Payment\Api\Data\ContractInterface;
use Reach\Payment\Api\Data\ContractSearchResultInterface;

/**
 * @api
 */
interface ContractRepositoryInterface
{
    /**
     * @param int $id
     * @return ContractInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param ContractInterface $contract
     * @return ContractInterface
     * @throws CouldNotSaveException
     */
    public function save(ContractInterface $contract);

    /**
     * @param ContractInterface $contract
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ContractInterface $contract);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return ContractSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
