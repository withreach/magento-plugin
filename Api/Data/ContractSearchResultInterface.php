<?php

namespace Reach\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ContractSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return ContractInterface[]
     */
    public function getItems();

    /**
     * @param ContractInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
