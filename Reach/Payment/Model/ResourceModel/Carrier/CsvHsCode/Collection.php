<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode;

/**
 * HS Codes Table
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model and item
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Reach\Payment\Model\Carrier\CsvHsCode::class,
            \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode::class
        );
    }

    /**
     * Initialize select, add country iso3 code and region name
     *
     * @return void
     */
    public function _initSelect()
    {
        parent::_initSelect();
    }

    /**
     * Add website filter to collection
     *
     * @param int $websiteId
     * @return \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Collection
     */
    public function setWebsiteFilter($websiteId)
    {
        return $this->addFieldToFilter('website_id', $websiteId);
    }
}
