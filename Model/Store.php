<?php

namespace Reach\Payment\Model;

use Magento\Framework\App\ObjectManager;

/**
 * Store model
 */
class Store extends \Magento\Store\Model\Store
{

    /** @var  \Magento\Framework\App\State */
    protected $state;

   /** @var  \Reach\Payment\Model\Currency */
    protected $currencyModel;

    /** @var  \Reach\Payment\Helper\Data */
    protected $reachHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\Currency $currencyModel
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Store\Model\ResourceModel\Store $resource
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Config\Model\ResourceModel\Config\Data $configDataResource
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Session\SidResolverInterface $sidResolver
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param Information $information
     * @param string $currencyInstalled
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param bool $isCustomEntryPoint
     * @param array $data optional generic object data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Currency $currencyModel,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\ResourceModel\Store $resource,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Config\Model\ResourceModel\Config\Data $configDataResource,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Store\Model\Information $information,
        $currencyInstalled,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $isCustomEntryPoint = false,
        array $data = []
    ) {
         parent::__construct(
             $context,
             $registry,
             $extensionFactory,
             $customAttributeFactory,
             $resource,
             $coreFileStorageDatabase,
             $configCacheType,
             $url,
             $request,
             $configDataResource,
             $filesystem,
             $config,
             $storeManager,
             $sidResolver,
             $httpContext,
             $session,
             $currencyFactory,
             $information,
             $currencyInstalled,
             $groupRepository,
             $websiteRepository,
             $resourceCollection,
             $isCustomEntryPoint,
             $data
         );

        $this->reachHelper = $reachHelper;
        $this->state = $state;
        $this->currencyModel = $currencyModel;
    }

    /**
     * Get allowed store currency codes
     *
     * If base currency is not allowed in current website config scope,
     * then it can be disabled with $skipBaseNotAllowed
     *
     * @param bool $skipBaseNotAllowed
     * @return array
     */
    public function getAvailableCurrencyCodes($skipBaseNotAllowed = false)
    {
        if (in_array($this->state->getAreaCode(), ['frontend','webapi_rest']) && $this->reachHelper->isReachCurrencyEnabled()) {
            if (!$this->isCountryApplicable()) {
                $this->currencyModel->_logger->debug("No Country Filtering");
                //shall remove this commented out section once the change is finalized
                //or I assess whether applying that mask ($skipBaseNotAllowed) on what is
                //returned from Reach API is needed or not.
                //return parent::getAvailableCurrencyCodes($skipBaseNotAllowed);
                return $this->currencyModel->getReachCurrencies();
            }
            if ($this->reachHelper->canAllowMultipleCurrency()) {
                $this->currencyModel->_logger->debug("Multiple Currencies");
                $codes = $this->currencyModel->getReachCurrencies();
                $this->currencyModel->_logger->debug("Currency code from Magento ".json_encode(parent::getAvailableCurrencyCodes($skipBaseNotAllowed)));
                $this->currencyModel->_logger->debug("Currency codes from Reach ".json_encode($codes));
                //what to do if nothing is returned from our api call?
                //and what if the intersection is empty
                if (count($codes)) {
                    $filteredCodes = array_intersect(parent::getAvailableCurrencyCodes($skipBaseNotAllowed), $codes);
                    return $filteredCodes;
                }
            } else {
                $this->currencyModel->_logger->debug("Single Currency");
                $localized  = $this->getLocalizedCurrency();
                if ($localized && isset($localized['currency'])) {
                    return [$localized['currency']];
                }
            }
        }
        return parent::getAvailableCurrencyCodes($skipBaseNotAllowed);
    }
    
    /**
     * get localized currency array
     * @return array|null
     */
    protected function getLocalizedCurrency()
    {

        if ($this->_session->getLocalize() !== null) {
            $locale  = $this->_session->getLocalize();
            return $locale;
        } else {
            $locale  = $this->currencyModel->getLocalizeCurrency();
            if ($locale) {
                $this->_session->setLocalize($locale);
                return $locale;
            }
        }
        return null;
    }

     /**
      * Get default store currency code
      *
      * @return string
      */
    public function getDefaultCurrencyCode()
    {

        if (in_array($this->state->getAreaCode(), ['frontend','webapi_rest']) && $this->reachHelper->isReachCurrencyEnabled()) {
            if (!$this->isCountryApplicable()) {
                return parent::getDefaultCurrencyCode();
            }
            $localized  = $this->getLocalizedCurrency();
            
            if ($localized && isset($localized['currency'])) {
                return $localized['currency'];
            }
        }
       
        return parent::getDefaultCurrencyCode();
    }

    /**
     * Check consumer country applicability
     *
     * @return boolean
     */
    public function isCountryApplicable()
    {
        if ($this->reachHelper->allowCurrencySpecificCountry()) {
            $allowed = $this->reachHelper->allowedCurrencyForCountries();
            $localized = $this->getLocalizedCurrency();
            $countries = explode(',', $allowed);
            if (!$localized || !isset($localized['country']) || !in_array($localized['country'], $countries)) {
                return false;
            }
        }
        return true;
    }
}
