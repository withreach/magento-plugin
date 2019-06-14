<?php

namespace Reach\Payment\Model;

use Magento\Framework\App\ObjectManager;

/**
 * ReachCurrency model
 *
 */
class ReachCurrency extends \Magento\Directory\Model\Currency
{
    /**
     * @var  \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var  \Reach\Payment\Model\Currency
     */
    protected $currencyModel;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\Currency $currencyModel
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param Currency\FilterFactory $currencyFilterFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param CurrencyConfig $currencyConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Currency $currencyModel,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Directory\Model\Currency\FilterFactory $currencyFilterFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry, 
            $localeFormat,
            $storeManager,
            $directoryHelper,
            $currencyFilterFactory,
            $localeCurrency,
            $resource,
            $resourceCollection,
            $data
        );
        $this->state = $context->getAppState();
        $this->currencyModel = $currencyModel;
        $this->reachHelper = $reachHelper;
    }
    /**
     * Get currency rate (only base => allowed)
     *
     * @param mixed $toCurrency
     * @return float
     */
    public function getRate($toCurrency)
    {
        if (in_array($this->state->getAreaCode(), ['frontend','webapi_rest']) && $this->reachHelper->isReachCurrencyEnabled()) {
            if (!$this->_storeManager->getStore()->isCountryApplicable()) {
                return parent::getRate($toCurrency);
            }
            $current_code = $this->_storeManager->getStore()->getCurrentCurrencyCode();
            $rates = $this->currencyModel->getReachCurrencyRates();
            if (isset($rates[$current_code])) {
                return $rates[$current_code];
            }
        }
        return parent::getRate($toCurrency);
    }

    /**
     * getCurrencyRates currency rates allowed
     * @param  object $currency
     * @param  object $toCurrencies can be null
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        if (in_array($this->state->getAreaCode(), ['frontend','webapi_rest']) && $this->reachHelper->isReachCurrencyEnabled()) {
            if (!$this->_storeManager->getStore()->isCountryApplicable()) {
                return parent::getRate($toCurrency);
            }
            $rates = $this->currencyModel->getReachCurrencyRates();
            if (count($rates)) {
                return $rates;
            }
        }
        return parent::getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Convert price to currency format
     *
     * @param   float $price
     * @param   mixed $toCurrency
     * @return  float
     * @throws \Exception
     */
    public function convert($price, $toCurrency = null)
    {
        $value = parent::convert($price, $toCurrency);
        if(gettype($toCurrency)=='string' && $toCurrency=="JPY")
        {
            return round($value);
        }
        elseif(gettype($toCurrency)=='object' && $toCurrency->getCurrencyCode()=="JPY")
        {
            return round($value);
        }
        return $value;
    }


}
