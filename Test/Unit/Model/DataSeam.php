<?php

namespace Reach\Payment\Test\Unit\Model;

use \DateTime;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\Pricing\PriceCurrencyInterface;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Reach\Payment\Helper\Data;

class DataSeam extends Data
{
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $enc,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $enc, $scopeConfig, $storeManager);
    }


}
