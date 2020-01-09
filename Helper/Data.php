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

    const API_URL = 'https://checkout.gointerpay.net/v2.19/';
    const SANDBOX_API_URL = 'https://checkout-sandbox.gointerpay.net/v2.19/';
    
    const DHL_API_URL = 'https://api.dhlecommerce.com/';
    const DHL_SANDBOX_API_URL = 'https://api-sandbox.dhlecommerce.com/';
    const DHL_ENABLE = 'reach/dhl/enable';
    const DHL_DUTY_LABEL = 'reach/dhl/duty_label';
    const DHL_DUTY_ALLOW_SPECIFIC = 'reach/dhl/allowspecific';
    const DHL_DUTY_ALLOW_SPECIFIC_COUNTRY = 'reach/dhl/specificcountry';
    const DHL_DUTY_OPTIONAL_SPECIFIC = 'reach/dhl/optional_allowspecific';
    const DHL_DUTY_OPTIONAL_SPECIFIC_COUNTRY = 'reach/dhl/optional_specificcountry';
    const DHL_DUTY_ALLOW_SHIPPING = 'reach/dhl/applicable_shipping';
    const DHL_API_KEY = 'reach/dhl/key';
    const DHL_API_SECRET = 'reach/dhl/api_secret';
    const DHL_ITEM_SELLER = 'reach/dhl/item_seller';
    const DHL_PICKUP_ACCOUNT = 'reach/dhl/pickup_account';
    const DHL_PRICIING_STRATEGY = 'reach/dhl/pricing_strategy';
    const DHL_DEFAULT_HS_CODE = 'reach/dhl/default_hs_code';

    const CONFIG_REACH_ENABLED = 'reach/global/active';
    const CONFIG_CURRENCY_OPTION = 'reach/global/display_currency_switch';
    const CONFIG_CURRENCY_ALLOWE_SPECIFIC = 'reach/global/allowspecific';
    const CONFIG_CURRENCY_SPECIFIC_COUNTRY = 'reach/global/specificcountry';
    const CONFIG_API_MODE = 'reach/global/mode';
    const CONFIG_MERCHANT_ID = 'reach/global/mearchant_id';
    const CONFIG_API_SECRET = 'reach/global/api_secret';

    const CONFIG_CC_OPEN_ONCTRACT = 'payment/reach_cc/allow_open_contract';

    const TESTFIELD = 'payment/reach_payment/testField';

    /**
     * Constant for payment
     */
    const XML_PATH_REACH = 'payment/';

    protected $currencyOption;

    /**
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $enc
    ) {
        $this->_enc = $enc;
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
     * Get Test Field
     * @return string
     */
    public function getTestField()
    {
        return $this->getConfigValue(self::TESTFIELD);
    }

     /**
     * Check Reach Enabled
     * @return boolean
     */
    public function getReachEnabled()
    {
        return $this->getConfigValue(self::CONFIG_REACH_ENABLED);
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
     * Get currencly allowed for specific countries config
     *
     * @return boolean
     */
    public function allowCurrencySpeicifcCountry()
    {
        return $this->getConfigValue(self::CONFIG_CURRENCY_ALLOWE_SPECIFIC);
    }

    /**
     * Get allowed currencies for country config
     *
     * @return boolean
     */
    public function allowedCurrencyForCountries()
    {
        return $this->getConfigValue(self::CONFIG_CURRENCY_SPECIFIC_COUNTRY);
    }

    /**
     * Get open contract allowed config
     *
     * @return boolean
     */
    public function getAllowOpenContract()
    {
        return $this->getConfigValue(self::CONFIG_CC_OPEN_ONCTRACT);
    }

    /**
     * Get Reach API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        if ($this->getConfigValue(self::CONFIG_API_MODE)) {
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
        return $this->getConfigValue(self::CONFIG_MERCHANT_ID);
    }

    /**
     * Get Reach API Secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->_enc->decrypt($this->getConfigValue(self::CONFIG_API_SECRET));
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
        return $this->getConfigValue(self::DHL_ENABLE);
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
     * Get DHL API Key
     *
     * @return string
     */
    public function getDhlApiKey()
    {
        return $this->getConfigValue(self::DHL_API_KEY);
    }


    /**
     * Get DHL API Secret
     *
     * @return string
     */
    public function getDhlApiSecret()
    {
        return $this->_enc->decrypt($this->getConfigValue(self::DHL_API_SECRET));
    }

    /**
     * Get DHL Pickup Account No.
     *
     * @return string
     */
    public function getDhlPickupAccount()
    {
        return $this->getConfigValue(self::DHL_PICKUP_ACCOUNT);
    }

    /**
     * Get DHL Item Seller.
     *
     * @return string
     */
    public function getDhlItemSeller()
    {
        return $this->getConfigValue(self::DHL_ITEM_SELLER);
    }

    /**
     * Get DHL Pricing strategy
     *
     * @return string
     */
    public function getDhlPricingStrategy()
    {
        return $this->getConfigValue(self::DHL_PRICIING_STRATEGY);
    }

    /**
     * Get DHL Default HS Code.
     *
     * @return string
     */
    public function getDhlDefaultHsCode()
    {
        return $this->getConfigValue(self::DHL_DEFAULT_HS_CODE);
    }

    /**
     * Get DHL Pricing strategy
     *
     * @return string
     */
    public function getDhlDutyLabel()
    {
        return $this->getConfigValue(self::DHL_DUTY_LABEL);
    }
    
    /**
     * Get DHL allowed specific
     *
     * @return boolean
     */
    public function getDhlAllowSpecific()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SPECIFIC);
    }

    /**
     * Get DHL allowed specific countries
     *
     * @return string
     */
    public function getDhlAllowedCountries()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SPECIFIC_COUNTRY);
    }

    /**
     * Get DHL duty optional for specific
     *
     * @return boolean
     */
    public function getDhlDutyOptionalSpecific()
    {
        return $this->getConfigValue(self::DHL_DUTY_OPTIONAL_SPECIFIC);
    }

    /**
     * Get DHL duty optional for specific countries
     *
     * @return string
     */
    public function getDhlDutyOptionalCountries()
    {
        return $this->getConfigValue(self::DHL_DUTY_OPTIONAL_SPECIFIC_COUNTRY);
    }

    /**
     * Get DHL duty applicable for shipping
     *
     * @return string
     */
    public function getDhlApplicableShippings()
    {
        return $this->getConfigValue(self::DHL_DUTY_ALLOW_SHIPPING);
    }
}
