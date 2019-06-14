<?php

namespace Reach\Payment\Model\Config\Backend;

use Magento\Framework\Model\AbstractModel;

class CsvHsCode extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCodeFactory
     */
    protected $csvHsCodeFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCodeFactory $CsvHsCodeFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCodeFactory $csvHsCodeFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->csvHsCodeFactory = $csvHsCodeFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        /** @var \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode $hdcode */
        $hdcode = $this->csvHsCodeFactory->create();
        $hdcode->uploadAndImport($this);
        return parent::afterSave();
    }
}
