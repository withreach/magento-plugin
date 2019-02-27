<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Reach\Payment\Block\Adminhtml\Carrier\CsvHsCode;

/**
 * HHL hs codes grid block
 * WARNING: This grid used for export hs codes
 *
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Website filter
     *
     * @var int
     */
    protected $_websiteId;

    /**
     * Condition filter
     *
     * @var string
     */
    protected $_conditionName;

    
    /**
     * @var \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\CollectionFactory $collectionFactory,
        \Reach\Payment\Model\Carrier\CsvHsCode $tablerate,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Define grid properties
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('hscodeGrid');
        $this->_exportPageSize = 10000;
    }

     /**
      * Prepare HS codes collection
      *
      * @return \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Collection
      */
    protected function _prepareCollection()
    {
        /** @var $collection \\Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Collection */
        $collection = $this->_collectionFactory->create();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare table columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'sku',
            ['header' => __('sku'), 'index' => 'sku']
        );

        $this->addColumn(
            'hs_code',
            ['header' => __('hs_code'), 'index' => 'hs_code']
        );

        return parent::_prepareColumns();
    }
}
