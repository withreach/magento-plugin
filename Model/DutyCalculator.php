<?php

namespace Reach\Payment\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class DutyCalculator implements \Reach\Payment\Api\DutyCalculatorInterface
{

    const XML_PATH_ORIGIN_COUNTRY_ID = 'shipping/origin/country_id';
    const XML_PATH_ORIGIN_REGION_ID = 'shipping/origin/region_id';

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory
     */
    protected $csvHsCodeFactory;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $regionModel;

    /**
     * @var \Reach\Payment\Model\Api\HttpRestFactory
     */
    protected $httpRestFactory;

    /**
     * @var \Reach\Payment\Api\Data\DutyResponseInterface
     */
    protected $response;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Constructor
     *
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory $csvHsCodeFactory
     * @param \Magento\Directory\Model\Region $regionModel
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Reach\Payment\Api\Data\DutyResponseInterface $response
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory $csvHsCodeFactory,
        \Magento\Directory\Model\Region $regionModel,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Reach\Payment\Api\Data\DutyResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteRepository    = $quoteRepository;
        $this->reachHelper    = $reachHelper;
        $this->checkoutSession    = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_scopeConfig       = $scopeConfig;
        $this->storeManager       = $storeManager;
        $this->response           = $response;
        $this->regionModel        = $regionModel;
        $this->priceCurrency      = $priceCurrency;
        $this->csvHsCodeFactory   = $csvHsCodeFactory;
        $this->httpRestFactory    = $httpRestFactory;
        $this->_logger = $logger;
    }

     /**
      * @inheritDoc
      */
    public function getDutyandTax($cartId, $shippingCharge, $shippingMethodCode, $shippingCarrierCode, $address, $apply = false)
    {
        try {
            $quote = $this->getQuoteById($cartId);
            $quote->collectTotals();
            $duty=0.00;
            
            if (!$this->allowDuty($address->getCountryId()) || !$this->allowShipping($shippingMethodCode, $shippingCarrierCode)) {
                $this->response->setSuccess(true);
                $this->response->setDuty($duty);
                return $this->response;
            }
            $accessToken = $this->getDhlAccessToken();

            // Enable lines with logger function to turn on logging for debugging.
            // $this->_logger->debug('---------------- getDutyandTax - START OF REQUEST----------------');
            // $this->_logger->debug('cartId:');
            // $this->_logger->debug(json_encode($cartId));
            // $this->_logger->debug('shippingCharge:');
            // $this->_logger->debug(json_encode($shippingCharge));
            // $this->_logger->debug('shippingMethodCode:');
            // $this->_logger->debug(json_encode($shippingMethodCode));
            // $this->_logger->debug('shippingCarrierCode:');
            // $this->_logger->debug(json_encode($shippingCarrierCode));
            // $this->_logger->debug('address:');
            // $this->_logger->debug(json_encode($address));
            // $this->_logger->debug('apply:');
            // $this->_logger->debug(json_encode($apply));
            // $this->_logger->debug('accessToken:');
            // $this->_logger->debug(json_encode($accessToken));

            if ($accessToken && $accessToken!='') {
                $request = $this->prepareRequest($shippingCharge, $address);
                $response = $this->getQuote($request, $accessToken);

                if (isset($response['feeTotals'])) {
                    foreach ($response['feeTotals'] as $charge) {
                        $duty += $charge['value'];
                    }
                    if ($apply || !$this->getIsOptional($address->getCountryId())) {
                        $duty = $this->priceCurrency->round($duty);
                        $quote = $this->checkoutSession->getQuote();
                        $baseCurrency = $this->storeManager->getStore()->getBaseCurrency();
                        $rate = $baseCurrency->getRate($baseCurrency->getCode());
                        $baseDuty = $duty / $rate;
                        $quote->setBaseReachDuty($baseDuty);
                        $quote->setReachDuty($duty);
                        $quote->setDhlQuoteId($response['quoteId']);
                        $quote->setDhlBreakdown(json_encode($response['feeTotals']));
                        $quote->save();
                    } else {
                        $quote->setBaseReachDuty(0);
                        $quote->setReachDuty(0);
                        $quote->setDhlQuoteId('');
                        $quote->setDhlBreakdown('');
                        $quote->save();
                    }
                    $this->response->setSuccess(true);
                    $this->response->setDuty($duty);
                    $this->response->setIsOptional($this->getIsOptional($address->getCountryId()));
                } else {
                    $this->response->setSuccess(false);
                    $this->response->setDuty(0);
                    if (isset($response['message'])) {
                        $this->response->setErrorMessage($response['message']);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->response->setSuccess(false);
            $this->response->setErrorMessage(
                __('Something went wrong while generating the DHL request: ' . $e->getMessage())
            );
        }
        // $this->_logger->debug('================ getDutyandTax - END OF REQUEST================');
        return $this->response;
    }
    
    /**
     * Get repository
     *
     * @return \Magento\Quote\Api\CartRepositoryInterface
     */
    public function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    /**
     * Get factory
     *
     * @return \Magento\Quote\Model\QuoteIdMaskFactory
     */
    public function getQuoteIdMaskFactory()
    {
        return $this->quoteIdMaskFactory;
    }

    /**
     * @inheritDoc
     */
    public function getQuoteById($cartId)
    {
        return $this->getQuoteRepository()->get($cartId);
    }

    /**
     * Check tax/duty optional/mandatory for shipping country
     *
     * @param string countryId
     * @return boolean
     */
    protected function getIsOptional($countryId)
    {
        if ($this->reachHelper->getDhlDutyOptionalSpecific()) {
            $allowed = $this->reachHelper->getDhlDutyOptionalCountries();
            $countries = explode(',', $allowed);
            if (!in_array($countryId, $countries)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check applicablity of shipping country
     *
     * @param string countryId
     * @return boolean
     */
    protected function allowDuty($countryId)
    {
        if ($this->reachHelper->getDhlAllowSpecific()) {
            $allowed = $this->reachHelper->getDhlAllowedCountries();
            $countries = explode(',', $allowed);
            if (!in_array($countryId, $countries)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check applicablity of selected shipping
     *
     * @param string $shippingMethodCode
     * @param string $shippingCarrierCode
     * @return boolean
     */
    protected function allowShipping($shippingMethodCode, $shippingCarrierCode)
    {
        $shippingMethods = $this->reachHelper->getDhlApplicableShippings();
        $shippingMethods = explode(',', $shippingMethods);
        return in_array($shippingMethodCode.'_'.$shippingCarrierCode, $shippingMethods);
    }

    /**
     * Prepare DHL quote request
     *
     * @param float $freightCharge
     * @param Magento\Quote\Api\Data\AddressInterface $shippingAddress
     * @return array
     */
    protected function prepareRequest($freightCharge, $shippingAddress)
    {
        $quote = $this->checkoutSession->getQuote();
       
        if ($quote->getId()) {
            $request=[];
                           
            $request['pickupAccount'] = $this->reachHelper ->getDhlPickupAccount();
            $request['itemSeller']= $this->reachHelper->getDhlItemSeller();
            $request['pricingStrategy']=$this->reachHelper->getPricingStrategy();
            $request['senderAddress']=$this->getShippingOrigin();//['state'=>'FL','country'=>'US'];
            $itemData['packageDetails']=[''];
            $request['packageDetails']['outputCurrency']=$quote->getQuoteCurrencyCode();
            $request['packageDetails']['freightCharge'] = ['value'=>$freightCharge,'currency'=>$quote->getQuoteCurrencyCode()];
            $request['packageDetails']["clearanceMode"] = $this->reachHelper->getClearanceMode();
            $request['packageDetails']["transportMode"] = $this->reachHelper->getTransportMode();
            $request['packageDetails']["endUse"] = $this->reachHelper->getEndUse();
            $request['customsDetails']=[];
            $request['consigneeAddress']=['state'=>$shippingAddress->getRegionCode(),'country'=>$shippingAddress->getCountryId()];
            foreach ($quote->getItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                $itemData=[];
                $itemData['itemId']=(string)$item->getId();
                $itemData['hsCode']=$this->getHsCode($item->getSku());
                if (!$itemData['hsCode']) {
                    $itemData['hsCode']=$this->reachHelper->getDhlDefaultHsCode();
                }
                $itemData['skuNumber']=$item->getSku();
                $itemData['itemValue']=['value'=>$item->getRowTotal()/$item->getQty(),'currency'=>$quote->getQuoteCurrencyCode()];
                $itemData['itemQuantity']=['value'=>$item->getQty(),'unit'=>"PCS"];
                $itemData['countryOfOrigin'] = $this->getCountryOfOrigin($item->getSku());
                if (!$itemData['countryOfOrigin']) {
                    $itemData['countryOfOrigin'] = $request['senderAddress']['country'];
                }
                $itemData['qualifiesForPreferentialTariffs'] = $this->reachHelper->getTariffStrategy();
                $request['customsDetails'][]=$itemData;
            }
            return $request;
        }
    }

    /**
     * Get Shipping origin of store
     *
     * @return string
     */
    protected function getShippingOrigin()
    {
        $origin = [];
        $region = $this->_scopeConfig->getValue(
            self::XML_PATH_ORIGIN_REGION_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        if (is_numeric($region)) {
            $this->regionModel->load($region);
            $region = $this->regionModel->getCode();
        }
        $origin['state']=$region;
        $origin['country']=$this->_scopeConfig->getValue(
            self::XML_PATH_ORIGIN_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        ;
        
        return $origin;
    }

    /**
     * Get SKU specific HS code
     *
     * @param string $sku
     * @return string
     */
    protected function getHsCode($sku)
    {
        $code = $this->csvHsCodeFactory->create()->getHsCode($sku);
        if ($code) {
            return $code;
        }
        return null;
    }

    /**
     * Get SKU specific Country of Origin 
     *
     * @param string $sku
     * @return string
     */
    protected function getCountryOfOrigin($sku)
    {
        $country_of_origin = $this->csvHsCodeFactory->create()->getCountryOfOrigin($sku);
        if ($country_of_origin) {
            return $country_of_origin;
        }
        return null;
    }

    /**
     * Get Quote from DHL
     *
     * @param array $request
     * @param string $accessToken
     * @return array
     */
    protected function getQuote($request, $accessToken)
    {
        $url = $this->reachHelper->getDhlApiUrl();
        $url .= 'flc/v1/quote';

        $rest = $this->httpRestFactory->create();
        $rest->setBearerAuth($accessToken);
        $rest->setUrl($url);
        // Uncomment following lines to see request params:
        // $this->_logger->debug('----------------GET QUOTE FROM DHL - START OF REQUEST----------------');
        // $this->_logger->debug(json_encode($url));
        // $this->_logger->debug(json_encode($accessToken));
        // $this->_logger->debug(json_encode($request));
        // $this->_logger->debug('================GET QUOTE FROM DHL - END OF REQUEST================');
        $response = $rest->executePost(json_encode($request));
        $result = $response->getResponseData();
        // $this->_logger->debug(json_encode($result));
        // $this->_logger->debug('================GET QUOTE FROM DHL - END OF REQUEST================');
        return $result;
    }

    /**
     * Retrieve DHL API access token
     *
     * @return string
     */
    protected function getDhlAccessToken()
    {
        $clientId = $this->reachHelper->getDhlApiKey();
        $clientSecret = $this->reachHelper->getDhlApiSecret();
        $url = $this->reachHelper->getDhlApiUrl();
        $basic = base64_encode($clientId.':'.$clientSecret);
        $url .= 'account/v1/auth/accesstoken';
        $rest = $this->httpRestFactory->create();
        $rest->setBasicAuth($basic);
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();

        // $this->_logger->debug('----------------GET DHL ACCESS TOKEN - START OF REQUEST----------------');
        // $this->_logger->debug(json_encode($clientId));
        // $this->_logger->debug(json_encode($clientSecret));
        // $this->_logger->debug(json_encode($url));
        // $this->_logger->debug(json_encode($response));
        // $this->_logger->debug(json_encode($result));
        // $this->_logger->debug('================GET DHL ACCESS TOKEN - END OF REQUEST================');

        if (isset($result['access_token'])) {
            return $result['access_token'];
        }
        return null;
    }
}
