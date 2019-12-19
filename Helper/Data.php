<?php

namespace Reach\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Helper Class
 */
class Data extends AbstractHelper
{
    const CONFIG_CURRENCY_OPTION    = 'reach/global/display_currency_switch';
    const CONFIG_API_MODE           = 'reach/global/mode';

    const API_URL                   = 'https://checkout.gointerpay.net/v2.19/';
    const SANDBOX_API_URL           = 'https://checkout-sandbox.gointerpay.net/v2.19/';
    const DHL_API_URL               = 'https://api.dhlecommerce.com/';
    const DHL_SANDBOX_API_URL       = 'https://api-sandbox.dhlecommerce.com/';

    const STASH_URL                 = 'https://stash.gointerpay.net/';
    const STASH_SANDBOX_URL         = 'https://stash-sandbox.gointerpay.net/';

    const WEBSITES_SCOPE            = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
    const STORES_SCOPE              = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;

    const REACH_ENABLE_PATH         = "payment/reach_payment/active";
    const REACH_API_MODE_PATH       = "payment/reach_payment/mode";
    const REACH_CURRENCY_SWITCH     = "payment/reach_payment/display_currency_switch";
    const REACH_SPECIFIC_PATH       = "payment/reach_payment/allowspecific";
    const REACH_COUNTRY_PATH        = "payment/reach_payment/specificcountry";
    const REACH_OPEN_CONTRACT_PATH  = "payment/reach_payment/reach_cc/allow_open_contract";
    const MERCHANT_ID_PATH          = "payment/reach_payment/merchantId";
    const API_SECRET_PATH           = "payment/reach_payment/api_secret";

    const DUTY_LABEL_PATH           = "payment/reach_payment/reach_dhl/duty_label";
    const DHL_ENABLE_PATH           = "payment/reach_payment/reach_dhl/reach_dhl_enable";
    const DHL_SPECIFIC_PATH         = "payment/reach_payment/reach_dhl/allowspecific";
    const DHL_SPECIFIC_COUNTRY_PATH = "payment/reach_payment/reach_dhl/specificcountry";
    const DHL_OPT_SPECIFIC_PATH     = "payment/reach_payment/reach_dhl/optional_allowspecific";
    const DHL_OPT_S_COUNTRY_PATH    = "payment/reach_payment/reach_dhl/optional_specificcountry";
    const DHL_CLIENT_ID_PATH        = "payment/reach_payment/reach_dhl/dhl_client_id";
    const DHL_SHIPPING_PATH         = "payment/reach_payment/reach_dhl/applicable_shipping";
    const DHL_CLIENT_SECRET_PATH    = "payment/reach_payment/reach_dhl/dhl_client_secret";
    const DHL_PICKUP_PATH           = "payment/reach_payment/reach_dhl/dhl_pickup_account";
    const DHL_SELLER_PATH           = "payment/reach_payment/reach_dhl/dhl_item_seller";
    const DHL_PRICING_PATH          = "payment/reach_payment/reach_dhl/pricing_strategy";
    const DHL_HS_CODE_PATH          = "payment/reach_payment/reach_dhl/default_hs_code";

    /**
     * Constant for payment
     */
    const XML_PATH_REACH = 'payment/';

    protected $currencyOption;

    protected $_scopeConfig;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $enc,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_enc = $enc;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * getConfigValue
     * gets config Value for frontend
     * @param  string $field Field id in system.xml
     * @param  int $storeId StoreID can be null
     * @return string|bool
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * getReachConfig
     * @param  string $code
     * @param  int $storeId
     * @return string|bool
     */
    public function getReachConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_REACH .'reach_gointerpay/'. $code, $storeId);
    }

    /**
     * Get Currency Options value REACH_CURRENCY_SWITCH
     *
     * @return string|null
     */
    public function getCurrencySwitch() {
        return $this->_scopeConfig->getValue(SELF::REACH_CURRENCY_SWITCH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_CURRENCY_SWITCH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_CURRENCY_SWITCH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get the API Mode from database
     *
     * @return void
     */
    public function getApiMode() {
        return $this->_scopeConfig->getValue(SELF::REACH_API_MODE_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_API_MODE_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_API_MODE_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
    * Returns DHL label displayed to users during checkout
    *
    * @return string
    */
    public function getDhlDutyLabel()
    {
        return $this->_scopeConfig->getValue(SELF::DUTY_LABEL_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DUTY_LABEL_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DUTY_LABEL_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Check Reach Enabled
     * @return boolean
     */
    public function getReachEnabled()
    {
        return $this->_scopeConfig->getValue(SELF::REACH_ENABLE_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_ENABLE_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_ENABLE_PATH, SELF::WEBSITES_SCOPE);
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
    public function getDisaplyBade()
    {
        return $this->getConfigValue(self::CONFIG_DISPLAY_BADGE);
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
            $this->currencyOption = $this->getConfigValue(self::CONFIG_CURRENCY_OPTION);
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
     * Get currency allowed for specific countries config  REACH_SPECIFIC_PATH
     *
     * @return boolean
     */
    public function allowCurrencySpecificCountry()
    {
        return $this->_scopeConfig->getValue(SELF::REACH_SPECIFIC_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_SPECIFIC_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_SPECIFIC_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get allowed currencies for country config
     *
     * @return boolean
     */
    public function allowedCurrencyForCountries()
    {
        return $this->_scopeConfig->getValue(SELF::REACH_COUNTRY_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_COUNTRY_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_COUNTRY_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get open contract allowed config
     *
     * @return boolean
     */
    public function getAllowOpenContract()
    {
        return $this->_scopeConfig->getValue(SELF::REACH_OPEN_CONTRACT_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::REACH_OPEN_CONTRACT_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::REACH_OPEN_CONTRACT_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get Reach API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        if ($this->getApiMode()) {
            return self::SANDBOX_API_URL;
        } else {
            return self::API_URL;
        }
    }

    /**
     * Get Reach Stash API URL
     *
     * @return string
     */
    public function getStashApiUrl()
    {
        if ($this->getApiMode()) {
            return self::STASH_SANDBOX_URL;
        } else {
            return self::STASH_URL;
        }
    }

    /**
     * Get Reach Merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_scopeConfig->getValue(SELF::MERCHANT_ID_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::MERCHANT_ID_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::MERCHANT_ID_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get Reach API Secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->_scopeConfig->getValue(SELF::API_SECRET_PATH, SELF::STORES_SCOPE) ?
            $this->_enc->decrypt( $this->_scopeConfig->getValue(SELF::API_SECRET_PATH, SELF::STORES_SCOPE)  ):
            $this->_enc->decript( $this->_scopeConfig->getValue(SELF::API_SECRET_PATH, SELF::WEBSITES_SCOPE) );
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
        return $this->_scopeConfig->getValue(SELF::DHL_ENABLE_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_ENABLE_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_ENABLE_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL API Url
     *
     * @return string
     */
    public function getDhlApiUrl()
    {
        if ($this->getConfigValue(self::CONFIG_API_MODE)) {
            return self::DHL_SANDBOX_API_URL;
        } else {
            return self::DHL_API_URL;
        }
    }

    /**
     * Get DHL API Key DHL_CLIENT_ID_PATH
     *
     * @return string
     */
    public function getDhlClientId()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_CLIENT_ID_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_CLIENT_ID_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_CLIENT_ID_PATH, SELF::WEBSITES_SCOPE);
    }


    /**
     * Get DHL API Secret
     *
     * @return string
     */
    public function getDhlClientSecret()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_CLIENT_SECRET_PATH, SELF::STORES_SCOPE) ?
            $this->_enc->decrypt($this->_scopeConfig->getValue(SELF::DHL_CLIENT_SECRET_PATH, SELF::STORES_SCOPE)) :
            $this->_enc->decrypt($this->_scopeConfig->getValue(SELF::DHL_CLIENT_SECRET_PATH, SELF::WEBSITES_SCOPE));
    }

    /**
     * Get DHL Pickup Account No. DHL_PICKUP_PATH
     *
     * @return string
     */
    public function getDhlPickupAccount()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_PICKUP_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_PICKUP_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_PICKUP_PATH, SELF::WEBSITES_SCOPE);
    }

     /**
     * Get DHL Item Seller. DHL_SELLER_PATH
     *
     * @return string
     */
    public function getDhlItemSeller()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_SELLER_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_SELLER_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_SELLER_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL Pricing strategy DHL_PRICING_PATH
     *
     * @return string
     */
    public function getDhlPricingStrategy()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_PRICING_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_PRICING_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_PRICING_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL Default HS Code. DHL_HS_CODE_PATH
     *
     * @return string
     */
    public function getDhlDefaultHsCode()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_HS_CODE_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_HS_CODE_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_HS_CODE_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL allowed specific DHL_SPECIFIC_PATH
     *
     * @return boolean
     */
    public function getDhlAllowSpecific()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL allowed specific countries
     *
     * @return string
     */
    public function getDhlAllowedCountries()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_COUNTRY_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_COUNTRY_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_SPECIFIC_COUNTRY_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL duty optional for specific DHL_OPT_SPECIFIC_PATH
     *
     * @return boolean
     */
    public function getDhlDutyOptionalSpecific()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_OPT_SPECIFIC_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_OPT_SPECIFIC_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_OPT_SPECIFIC_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL duty optional for specific countries DHL_TOP_SPECIFIC_COUNTRY
     *
     * @return string
     */
    public function getDhlDutyOptionalCountries()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_OPT_S_COUNTRY_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_OPT_S_COUNTRY_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_OPT_S_COUNTRY_PATH, SELF::WEBSITES_SCOPE);
    }

    /**
     * Get DHL duty applicable for shipping DHL_SHIPPING_PATH
     *
     * @return string
     */
    public function getDhlApplicableShippings()
    {
        return $this->_scopeConfig->getValue(SELF::DHL_SHIPPING_PATH, SELF::STORES_SCOPE) ?
            $this->_scopeConfig->getValue(SELF::DHL_SHIPPING_PATH, SELF::STORES_SCOPE) :
            $this->_scopeConfig->getValue(SELF::DHL_SHIPPING_PATH, SELF::WEBSITES_SCOPE);
    }
}
