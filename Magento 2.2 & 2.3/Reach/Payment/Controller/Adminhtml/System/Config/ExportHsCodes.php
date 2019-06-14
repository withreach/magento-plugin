<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Reach\Payment\Controller\Adminhtml\System\Config;

use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportHsCodes extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    protected $_publicActions = ['exporthscodes'];

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker $sectionChecker
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_fileFactory = $fileFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Export HS codes in csv format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'hscodes.csv';
        /** @var $gridBlock \Reach\Payment\Block\Adminhtml\Carrier\CsvHsCode\Grid */
        $gridBlock = $this->_view->getLayout()->createBlock(
            \Reach\Payment\Block\Adminhtml\Carrier\CsvHsCode\Grid::class
        );
        $content = $gridBlock->getCsvFile();
        
        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
