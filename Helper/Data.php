<?php

namespace Reach\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Helper Class
 */
class Data extends AbstractHelper
{

    const API_URL = 'https://checkout.gointerpay.net/v2.19/';
    const STASH_URL = 'https://stash.gointerpay.net/';
    const STASH_SANDBOX_URL = 'https://stash-sandbox.gointerpay.net/';
    const SANDBOX_API_URL = 'https://checkout-sandbox.gointerpay.net/v2.19/';

    const DHL_API_URL = 'https://api.dhlecommerce.com/';
    const DHL_SANDBOX_API_URL = 'https://api-sandbox.dhlecommerce.com/';
    const DHL_ENABLE = 'reach/dhl/enable';
    const DHL_DUTY_LABEL = 'reach/dhl/duty_label';
    const DHL_DUTY_ALLOW_SPECIFIC = 'reach/dhl/allowspecific';
    const DHL_DUTY_ALLOW_SPECIFIC_COUNTRY = 'reach/dhl/specificcountry';
    const DHL_DUTY_OPTIONAL_SPECIFIC = 'reach/dhl/optional_allowspecific';
    const DHL_DUTY_OPTIONAL_SPECIFIC_COUNTRY = 'reach/dhl/optional_specificcountry';


    //DHL_DUTY_ALLOW_SHIPPING would be needed for MAG-90 as well
    const DHL_DUTY_ALLOW_SHIPPING = "reach/dhl/applicable_shipping";
    const DHL_API_KEY = "reach/dhl/key";
    const DHL_API_SECRET = "reach/dhl/api_secret";
    const DHL_ITEM_SELLER = "reach/dhl/item_seller";
    const DHL_PICKUP_ACCOUNT =  "reach/dhl/pickup_account";
    const DHL_DEFAULT_HS_CODE =  "reach/dhl/default_hs_code";

    const CONFIG_REACH_ENABLED = 'reach/global/active';
    const CONFIG_CURRENCY_OPTION = 'reach/global/display_currency_switch';
    const CONFIG_CURRENCY_ALLOW_SPECIFIC = 'reach/global/allowspecific';
    const CONFIG_CURRENCY_SPECIFIC_COUNTRY = 'reach/global/specificcountry';
    const CONFIG_API_MODE = 'reach/global/mode';
    const CONFIG_MERCHANT_ID = 'reach/global/mearchant_id';
    const CONFIG_API_SECRET = 'reach/global/api_secret';


    const CONFIG_CC_OPEN_CONCTRACT = 'payment/reach_cc/allow_open_contract';

    const DHL_PREF_TARIFFS          = "reach/dhl/pref_tariffs";
    const DHL_PRICING_STRATEGY_PATH = "reach/dhl/pricing_strategy";
    const DHL_CLEARANCE_MODE_PATH   = "reach/dhl/clearance_mode";
    const DHL_END_USE_PATH          = "reach/dhl/end_use";
    const DHL_TRANSPORT_MODE_PATH   = "reach/dhl/transport_mode";
    const SANDBOX_MODE = 1;
    //No getter for this two yet
    const DHL_IMPORT_CSV_HS_CODE_PATH ="reach/dhl/import_csv_hs_code";
    const DHL_EXPORT_CSV_HS_CODE_PATH ="reach/dhl/export_csv_hs_code";
    /**
     * Constant for payment
     */
    const XML_PATH_REACH = 'payment/';

    protected $currencyOption;
    protected $_enc;
    protected $config;
    protected $storeManager;

    /**
     * @param Context $context
     * @param EncryptorInterface $enc
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $enc,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_enc = $enc;
        $this->config = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);

    }

    /**
     * @param string $path
     * @return array
     * <FIXME>
     * Need investigation: how to effectively know the correct scope code and storeID  properly.
     * wondering is it possible that one scope type has multiple scope codes?
     * Thinking about looking at the dev test fixture examples that came with Magento
     * apparently.
     */


    /**
     * Get Reach Stash API URL
     *
     * @return string
     */
    public function getStashApiUrl()
    {
        if ($this->getConfigValue(self::CONFIG_API_MODE) == self::SANDBOX_MODE) {
            return self::STASH_SANDBOX_URL;
        } else {
            return self::STASH_URL;
        }
    }

    /**
     * Get configuration value from highest priority scope (that is not undefined).
     * If all else fails then returns configured value from the default scope.
     * gets config Value for frontend
     * @param  string $path in config.xml
     * @param  int $storeId StoreID can be null
     * @return string|bool
     */
    public function getConfigValue($path, $storeID = null)
    {
        $valueInStore = $this->config->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeID);
        if (isset($valueInStore)) {
            return  $valueInStore;
        }
        $valueInWebsite = $this->config->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if (isset($valueInWebsite)) {
            return  $valueInWebsite;
        }
        $valueInDefault = $this->config->getValue($path,\Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

        return $valueInDefault; //At this point other higher priority scopes are undefined; if it is undefined too
                                // then it would be treated as false

    }


    /**
     * getReachConfig
     * @param  string $code

     * @return string|bool
     */
    public function getReachConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_REACH .'reach_gointerpay/'. $code, $this->storeManager->getStore()->getId());
    }

    /**
     * Reading whether item/product qualifies for preferential tariffs
     *
     */
    public function getPrefTariffs() {

        return $this->getConfigValue(self::DHL_PREF_TARIFFS, $this->storeManager->getStore()->getId());
    }

    //The following two methods can be combined
    /**
     * Reading state/province of shipping origin
     * @param  string $xmlPathOriginRegionID
     * @param int $storeID
     * @return string|null
     */
    public function getShippingOriginState($xmlPathOriginRegionID, $storeID) {
        return $this->getConfigValue($xmlPathOriginRegionID, $storeID);
    }

    /**
     * Reading country of shipping origin
     * @param  string $xmlPathOriginCountryID
     * @param int $storeID
     * @return string|null
     */
    public function getShippingOriginCountry($xmlPathOriginCountryID, $storeID) {
        return $this->getConfigValue($xmlPathOriginCountryID, $storeID);
    }

    /**
     * Get Transport Mode for DHL DHL_TRANSPORT_MODE_PATH
     *
     */
    public function getTransportMode() {
        return $this->getConfigValue(self::DHL_TRANSPORT_MODE_PATH, $this->storeManager->getStore()->getId());
    }

    /**
     * Get End Use DHL_END_USE_PATH
     *
     * @return string
     */
    public function getEndUse() {
        return $this->getConfigValue(self::DHL_END_USE_PATH, $this->storeManager->getStore()->getId());
    }

    /**
     * Get Clearance Mode DHL_CLEARANCE_MODE_PATH
     *
     * @return string
     */
    public function getClearanceMode() {
        return $this->getConfigValue(self::DHL_CLEARANCE_MODE_PATH, $this->storeManager->getStore()->getId());
    }

    /**
     * Get Pricing Strategy DHL_PRICING_STRATEGY_PATH
     *
     * @return string
     */
    public function getPricingStrategy() {
        return $this->getConfigValue(self::DHL_PRICING_STRATEGY_PATH, $this->storeManager->getStore()->getId());
    }


    /**
     * Check Reach Enabled
     * @return boolean
     */
    public function getReachEnabled()
    {
        return $this->getConfigValue(self::CONFIG_REACH_ENABLED, $this->storeManager->getStore()->getId());
    }

    /**
     * getCoreSession
     * @return object
     */
    public function getCoreSession()
    {
        $objmanager = ObjectManager::getInstance();
        $core_session = $objmanager->create('Magento\Framework\Session\SessionManagerInterface');
        return $core_session;
    }


    /**
     * getCheckoutSession
     * @return object
     */
    public function getCheckoutSession()
    {
        $objmanager = ObjectManager::getInstance();
        $checkout_session = $objmanager->create('Magento\Checkout\Model\Session');
        return $checkout_session;
    }

    /**
     * Get display badge config
     *
     * @return boolean
     */
    //<FIXME> I see spelling mistake
    //Also where is the self::CONFIG_DISPLAY_BADGE constant even defined?

    public function getDisaplyBade()
    {
        return $this->getConfigValue(self::CONFIG_DISPLAY_BADGE, $this->storeManager->getStore()->getId());
    }

    /**
     * Get currency enable config
     *
     * @return boolean
     */
    public function isReachCurrencyEnabled()
    {
        if(!$this->getReachEnabled())
        {
            return false;
        }
        if ($this->currencyOption === null) {
            $this->currencyOption = $this->getConfigValue(self::CONFIG_CURRENCY_OPTION, $this->storeManager->getStore()->getId());
        }
        return in_array($this->currencyOption, ['customer','reach']);
    }

    /**
     * Get multi currency allow config
     *
     * @return boolean
     */
    public function canAllowMultiPleCurrency()
    {
        return $this->currencyOption == 'customer';
    }

    /**
     * Get currencly allowed for specific countries config
     *
     * @return boolean
     */
    public function allowCurrencySpeicifcCountry()
    {
        return $this->getConfigValue(self::CONFIG_CURRENCY_ALLOW_SPECIFIC, $this->storeManager->getStore()->getId());
    }

    /**
     * Get allowed currencies for country config
     *
     * @return boolean
     */
    public function allowedCurrencyForCountries()
    {
        return $this->getConfigValue(self::CONFIG_CURRENCY_SPECIFIC_COUNTRY, $this->storeManager->getStore()->getId());
    }

    /**
     * Get open contract allowed config
     *
     * @return boolean
     */
    public function getAllowOpenContract()
    {
        return $this->getConfigValue(self::CONFIG_CC_OPEN_CONCTRACT, $this->storeManager->getStore()->getId());
    }

    /**
     * Get Reach API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        if ($this->getConfigValue(self::CONFIG_API_MODE, $this->storeManager->getStore()->getId()) == self::SANDBOX_MODE) {
            return self::SANDBOX_API_URL;
        } else {
            return self::API_URL;
        }
    }

    /**
     * Get Reach Merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getConfigValue(self::CONFIG_MERCHANT_ID, $this->storeManager->getStore()->getId());
    }

    /**
     * Get Reach API Secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->_enc->decrypt($this->getConfigValue(self::CONFIG_API_SECRET, $this->storeManager->getStore()->getId()));
    }

    /**
     * Get Reach Checkout API URL
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getApiUrl().'checkout';
    }

    /**
     * Get Reach Capture API URL
     *
     * @return string
     */
    public function getCaptureUrl()
    {
        return $this->getApiUrl().'capture';
    }

    /**
     * Get Reach cancel API URL
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getApiUrl().'cancel';
    }

    /**
     * Get Reach Refund API URL
     *
     * @return string
     */
    public function getRefundUrl()
    {
        return $this->getApiUrl().'refund';
    }

    /**
     * Get Reach opencontract API URL
     *
     * @return string
     */
    public function getOpenContractUrl()
    {
        return $this->getApiUrl().'openContract';
    }

    /**
     * Get Reach query API URL
     *
     * @return string
     */
    public function getQueryUrl()
    {
        return $this->getApiUrl().'query';
    }

    /**
     * Check DHL Tax-Duties Enabled
     *
     * @return boolean
     */
    public function getDhlEnabled()
    {
        return $this->getConfigValue(self::DHL_ENABLE, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL API Url
     *
     * @return string
     */
    public function getDhlApiUrl()
    {
        if ($this->getConfigValue(self::CONFIG_API_MODE, $this->storeManager->getStore()->getId())
            == self::SANDBOX_MODE) {
            return self::DHL_SANDBOX_API_URL;
        } else {
            return self::DHL_API_URL;
        }
    }

    /**
     * Get DHL API Key
     *
     * @return string
     */
    public function getDhlApiKey()
    {
        return $this->getConfigValue(self::DHL_API_KEY, $this->storeManager->getStore()->getId());
    }


    /**
    /**
     * Get DHL API Secret
     *
     * @return string
     */
    public function getDhlApiSecret()
    {
        return $this->_enc->decrypt($this->getConfigValue(self::DHL_API_SECRET, $this->storeManager->getStore()->getId()));
    }

    /**
     * Get DHL Pickup Account No.
     *
     * @return string
     */
    public function getDhlPickupAccount()
    {
        return $this->getConfigValue(self::DHL_PICKUP_ACCOUNT, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL Item Seller.
     *
     * @return string
     */
    public function getDhlItemSeller()
    {
        return $this->getConfigValue(self::DHL_ITEM_SELLER, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL Default HS Code.
     *
     * @return string
     */
    public function getDhlDefaultHsCode()
    {
        return $this->getConfigValue(self::DHL_DEFAULT_HS_CODE, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL Pricing strategy
     *
     * @return string
     */
    public function getDhlDutyLabel()
    {
        return $this->getConfigValue(self::DHL_DUTY_LABEL, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL allowed specific
     *
     * @return boolean
     */
    public function getDhlAllowSpecific()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SPECIFIC, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL allowed specific countries
     *
     * @return string
     */
    public function getDhlAllowedCountries()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SPECIFIC_COUNTRY, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL duty optional for specific
     *
     * @return boolean
     */
    public function getDhlDutyOptionalSpecific()
    {
        return $this->getConfigValue(self::DHL_DUTY_OPTIONAL_SPECIFIC, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL duty optional for specific countries
     *
     * @return string
     */
    public function getDhlDutyOptionalCountries()
    {
        return $this->getConfigValue(self::DHL_DUTY_OPTIONAL_SPECIFIC_COUNTRY, $this->storeManager->getStore()->getId());
    }

    /**
     * Get DHL duty applicable for shipping
     *
     * @return string
     */
    public function getDhlApplicableShippings()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SHIPPING, $this->storeManager->getStore()->getId());
    }
}
