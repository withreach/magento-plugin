<?php

namespace Reach\Payment\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;

/**
 * Reach HS code model
 *
 * @api
 * @since 100.0.2
 */
class CsvHsCode extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context,
     * @param \Magento\Framework\Registry $registry,
     * @param \Reach\Payment\Helper\Data $reachHelper,
     * @param \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode $resource,
     * @param \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Collection $collection,
     * @param array $data = []
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode $resource,
        \Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode\Collection $collection,
        array $data = []
    ) {

        $this->reachHelper = $reachHelper;
        parent::__construct($context, $registry, $resource, $collection, $data);
    }

     /**
      * Initialization
      *
      * @return void
      */
    protected function _construct()
    {
        $this->_init(\Reach\Payment\Model\ResourceModel\Carrier\CsvHsCode::class);
    }
}
