<?php

namespace Reach\Payment\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Reach\Payment\Api\Data\ContractInterface;
use Reach\Payment\Api\Data\ContractSearchResultInterface;
use Reach\Payment\Api\Data\ContractSearchResultInterfaceFactory;
use Reach\Payment\Api\ContractRepositoryInterface;
use Reach\Payment\Model\ContractFactory;
use Reach\Payment\Model\ResourceModel\Contract;
use Reach\Payment\Model\ResourceModel\Contract\CollectionFactory;

class ContractRepository implements ContractRepositoryInterface
{
    /**
     * @var ContractFactory
     */
    private $contractFactory;

    /**
     * @var Contract
     */
    private $contractResource;

    /**
     * @var CollectionFactory
     */
    private $contractCollectionFactory;

    /**
     * @var ContractSearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        ContractFactory $contractFactory,
        Contract $contractResource,
        CollectionFactory $contractCollectionFactory,
        ContractSearchResultInterfaceFactory $contractSearchInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->contractFactory = $contractFactory;
        $this->contractResource = $contractResource;
        $this->contractCollectionFactory = $contractCollectionFactory;
        $this->searchResultFactory = $contractSearchInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param int $id
     * @return ContractInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $contract = $this->contractFactory->create();
        $this->contractResource->load($contract, $id, 'reach_contract_id');
        if (!$contract->getId()) {
            throw new NoSuchEntityException(__('Unable to find Reach contract with ID "%1"', $id));
        }
        return $contract;
    }

    /**
     * @param ContractInterface $contract
     * @return ContractInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ContractInterface $contract)
    {
        $this->contractResource->save($contract);
        return $contract;
    }

    /**
     * @param ContractInterface $contract
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ContractInterface $contract)
    {
        try {
            $this->contractResource->delete($contract);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the entry: %1', $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return ContractSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->contractCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
