<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Reach\Payment\Block\Adminhtml\Customer\Edit\Tab\View;
 
use Magento\Customer\Controller\RegistryConstants;
 
/**
 * Adminhtml customer recent orders grid block
 */
class SavedCard extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
 
    /**
     * @var \Magento\Sales\Model\Resource\Order\Grid\CollectionFactory
     */
    protected $_collectionFactory;
 
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \ForaStudio\Measurement\Model\ResourceModel\Measurementprofile\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Reach\Payment\Model\ResourceModel\Contract\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
         $this->_logger = $logger;
        $this->_coreRegistry = $coreRegistry;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }
 
    /**
     * Initialize the orders grid.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reach_customer_savedcard_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setSortable(false);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }
    /**
     * {@inheritdoc}
     */
    protected function _preparePage()
    {
        $this->getCollection()->setPageSize(10)->setCurPage(1);
    }
 
    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
       
        $customer_id = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $profile_collection = $this->_collectionFactory->create();
        $profile_collection->addFieldToFilter('customer_id', $customer_id);
        $this->setCollection($profile_collection);
        return parent::_prepareCollection();
    }
 
    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'method',
            ['header' => __('Card Type'), 'align' => 'left', 'index' => 'method', 'width' => 10]
        );
        $this->addColumn(
            'identifier',
            ['header' => __('Card Number'), 'align' => 'left', 'index' => 'identifier']
        );
        
        $this->addColumn(
            'created_at',
            ['header' => __('Saved Date'), 'index' => 'created_at', 'type' => 'date', 'width' => '140px']
        );

        $this->addColumn(
            'expire_at',
            ['header' => __('Expiry Date'), 'index' => 'expire_at', 'type' => 'date', 'width' => '140px']
        );

        return parent::_prepareColumns();
    }
 
    /**
     * Get headers visibility
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getHeadersVisibility()
    {
        return $this->getCollection()->getSize() >= 0;
    }
}
