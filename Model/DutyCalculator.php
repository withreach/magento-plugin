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
     * Sets quote and checkout Session state based on whether duty and tax is applicable or not
     * @param Magento\Quote\Api\Data\AddressInterface $address
     * @param bool $apply  - whether duty is applicable or not
     *
     */
    public function handleTaxApplicability($address, $apply)
    {
        $this->_logger->debug('In handleTaxApplicability method');
        $this->_logger->debug($this->checkoutSession->getReachDuty());
        $this->_logger->debug($address->getCountryId());
        $this->_logger->debug($address->getRegionCode());
        $this->_logger->debug($this->checkoutSession->getPrevCountry());
        $this->_logger->debug($this->checkoutSession->getPrevRegion());
        $this->_logger->debug($apply);
        $quote = $this->checkoutSession->getQuote();

        $this->response->setIsOptional($this->getIsOptional($address->getCountryId()));
        $this->response->setSuccess(true);
        $this->response->setDuty($this->checkoutSession->getReachDuty()); //the DT checkbox and label
        // should still appear if this amount is more than 0 (irrespective of whether the user chose to apply it or not on
        // computation of total landed cost/billing)
        $isDutyOptional = $this->getIsOptional($address->getCountryId());
        $this->_logger->debug("Value of apply ".$apply);
        $this->_logger->debug("Value of isDutyOptional ".$isDutyOptional);

        if ($apply || !$isDutyOptional) {
           $this->_logger->debug("Duty should be applied (due to user selection or the value of the 'apply' ".
               " parameter that is passed to this function or because duty is not optional for the chosen country ".
               "(shipment address)");
           $quote->setReachDuty($this->checkoutSession->getReachDuty());
           $quote->setDhlQuoteId($this->checkoutSession->getDhlQuoteId());
        }
        else {
            $this->_logger->debug("Duty should not be applied.");
            //as apply duty and tax(DT) is not selected or it is optional; duty and tax would not be used in total
            // pricing; so setting all relevant quote values to zero
            $quote->setReachDuty(0);
        }
        $quote->save();//this is needed so that different aspects related to a quote are available on other
        // pages/propagate to other pages (without changing existing code on those pages).
    }


    /** handles when duty is not applicable (based on some configuration) for the country
     * or shipping is not allowed for the country
     * and saves proper state of quote so that the information is available beyond this page/area
     * @param float $duty
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function handleNoDutyOrShippingCase($duty, $quote)
    {
        $this->response->setSuccess(true);
        $this->response->setDuty($duty);
        $this->checkoutSession->setReachDuty($duty);
        //Need the following two assignments to make sure that the country comparison based initiation or prevention of
        //DHL call works properly.
        //Otherwise the DHL call is prevented even when we need one (due to the fact that even though the
        //country selection has changed that is not tracked/captured).
        $this->checkoutSession->setPrevCountry('');
        $this->checkoutSession->setPrevRegion('');
        $quote->setReachDuty($duty) ;
        $this->_logger->debug('In duty or shipping not allowed section');
        $quote->save();
    }

    /** Sets appropriate response (as per DutyResponseInterface) values
     * when state/province is (needed for proper duty calculation
     * but) not yet specified
     * @param float $duty
     */
    public function handleStateUnspecifiedCase($duty)
    {
       $quote = $this->checkoutSession->getQuote();
       $this->response->setSuccess(true);
       $this->response->setDuty($duty);
       $this->_logger->debug('Special country where state selection is necessary before initiating DHL API call;'.
                'but state is not selected anyway.');
    }


    /** Sets appropriate response (\Reach\Payment\Api\Data\DutyResponseInterface) values
     *  when both state/province and country are specified
     * @param Magento\Quote\Api\Data\AddressInterface $address
     * @param bool $apply
     */
    public function handleCaseWithCountryAndStateSpecified($address, $apply)
    {
        $quote = $this->checkoutSession->getQuote();
        $this->response->setDuty($quote->getReachDuty());
        $this->handleTaxApplicability($address, $apply);
        $this->_logger->debug('Country is specified but state is not selected (when both are needed); so we would'.
            'prevent call to DHL API');
    }


    /** Uses already (recently) retrieved duty from DHL
     * @param bool $apply
     * @param \Magento\Quote\Model\Quote $quote
     * @param Magento\Quote\Api\Data\AddressInterface $address
     */
    public function handleCaseWhereDutyIsAlreadyRetrieved($apply, $quote, $address)
    {
        if ($apply) {//user indicated that (s)he wants to apply duty and tax during calculation of billing
            $quote->setReachDuty($this->checkoutSession->getReachDuty());
            $this->response->setDuty($this->checkoutSession->getReachDuty());
            $this->handleTaxApplicability($address, $apply);
            $this->_logger->debug('User wants to apply the D&T. Value of apply ::' . $apply);
            $quote->setBaseReachDuty($this->checkoutSession->getBaseReachDuty());
            $quote->setReachDuty($this->checkoutSession->getReachDuty());
            $quote->setDhlQuoteId($this->checkoutSession->getDhlQuoteId());
            $quote->setDhlBreakdown($this->checkoutSession->getDhlBreakdown());
            $this->_logger->debug($quote->getReachDuty());
        }
        else {//user indicated that (s)he does not  want to apply duty and tax during calculation of billing
            $quote->setReachDuty(0);
            //should we reset DhlQuoteId and DhlBreakdown too so as not to break any reporting?
            //I am assuming that we do
            $quote->setBaseReachDuty(0);
            $quote->setDhlQuoteId('');
            $quote->setDhlBreakdown('');
            $this->_logger->debug('User does not want to apply the D&T. Value of apply ::' . $apply);
        }

        $quote->save();
        $this->response->setDuty($this->checkoutSession->getReachDuty());
    }

    /** Adjust returned duty value from DHL and additionally keep track of different data that came back from
     * DHL API call and saves proper state of quote so that the information is available beyond this page/area
     * @param float $duty
     * @param Magento\Quote\Api\Data\AddressInterface $address
     * @param bool $apply
     * @param  Magento\Framework\App\Response $response
     */
    public function fillOutQuoteAndSessionUsingFeeReturned($duty, $address, $apply, $response)
    {
        //dealing with whether duty and tax related checkbox is selected or not
        //or whether applying duty is a must for that country or not (a setting in magento admin
        //panel)
        //filling out both Quote and session state as appropriate
        //DHL quoteID is already saved into session; so not doing it here again
        $quote = $this->checkoutSession->getQuote();
        $duty_adjusted = $this->priceCurrency->round($duty); //copied over pre-existing code
        //but are we supposed to round always?
        //should not that be based on corresponding admin setting?
        $this->checkoutSession->setReachDuty($duty_adjusted);

        if ($apply || !$this->getIsOptional($address->getCountryId())) {
            //checkbox selection was duty should be applied
            //or applying duty is a must for that country
            $baseCurrency = $this->storeManager->getStore()->getBaseCurrency();
            $rate = $baseCurrency->getRate($baseCurrency->getCode());
            $baseDuty = $duty_adjusted / $rate;
            $quote->setBaseReachDuty($baseDuty);
            $this->checkoutSession->setBaseReachDuty($baseDuty);
            $quote->setReachDuty($duty_adjusted);
            $this->checkoutSession->setReachDuty($duty_adjusted);
            $quote->setDhlQuoteId($response['quoteId']);
            $quote->setDhlBreakdown(json_encode($response['feeTotals']));
            $this->checkoutSession->setDhlBreakdown($quote->getDhlBreakdown());
            $this->checkoutSession->setApply(true); //checkbox selection /corresponding passed value
            $this->checkoutSession->setDhlQuoteId($response['quoteId']);
            //implies that duty should be applied
            $this->_logger->debug('Apply block immediately after DHL call');
        } else {
            //checkbox selection or parameter passed or admin config (as relevant to the selected country of shipment)
            //implies that the duty should not be applied
            $quote->setBaseReachDuty(0);
            $quote->setReachDuty(0);
            $this->checkoutSession->setBaseReachDuty(0);
            $this->checkoutSession->setReachDuty($duty_adjusted);
            $quote->setDhlQuoteId('');
            $quote->setDhlBreakdown('');
            $this->checkoutSession->setDhlBreakdown('');
            $this->checkoutSession->setApply(false);
            $this->_logger->debug('Do not Apply block immediately after DHL call');
        }

        $quote->save();
        $this->response->setSuccess(true);
        $this->response->setDuty($duty_adjusted);
        $this->response->setIsOptional($this->getIsOptional($address->getCountryId()));
    }

    /**
     * Deals with a case when DHL API call does not return a value for duty
     * @param Magento\Framework\App\Response $response
     */
    public function fillOutResponseAndSessionOnError($response)
    {
        $this->response->setSuccess(false);
        $this->response->setDuty(0);

        if (isset($response['message'])) { //error message from DHL duty and tax api call?
            //if yes then do we want to call the API again?
            //Assuming we do; we are resetting previous country and region value so that we can reenter the blcok to make
            //DHL API call
            $this->checkoutSession->setResponseErrorMessage($response['message']);
            $this->response->setErrorMessage($response['message']);
            $this->checkoutSession->setPrevCountry('');
            $this->checkoutSession->setPrevRegion('');
        }
    }

    /**
     * Retrieves duty value for a country or (country, state) combo
     * and set state of quote, response, session accordingly/as per the value returned
     * @param $cartId
     * @param Magento\Quote\Api\Data\AddressInterface $address
     * @param float $shippingCharge
     * @param float $duty
     * @param bool $apply
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function callDHLDutyTaxApi($cartId, $address, $shippingCharge, $duty, $apply, $quote)
    {
        $accessToken = $this->getDhlAccessToken();

        //Right moment to make a DHL api call for getting 'Duty and Tax' value
        if ($accessToken && $accessToken != '') {
            $this->checkoutSession->setCartId($cartId);
            //storing selection of country and state to be able to compare it against new set of values
            //when a potential buyer inputs more data
            $this->checkoutSession->setPrevCountry($address->getCountryId());
            $this->checkoutSession->setPrevRegion($address->getRegionCode());

            $request = $this->prepareRequest($shippingCharge, $address);
            $this->_logger->debug('---------------- Making DHL API call to get Duty and Tax ----------------');
            $response = $this->getQuote($request, $accessToken);

            $this->checkoutSession->setDhlQuoteId($response['quoteId']);
            $quote->setDhlQuoteId($response['quoteId']);

            //fee value came from DHL
            if (isset($response['feeTotals'])) {
                foreach ($response['feeTotals'] as $charge) {
                    $duty += $charge['value'];
                }
                $this->fillOutQuoteAndSessionUsingFeeReturned($duty, $address, $apply, $response);

            } else {
                $this->fillOutResponseAndSessionOnError($response);

            }
        }
    }
     /**
      * @inheritDoc
      */
    public function getDutyandTax($cartId, $shippingCharge, $shippingMethodCode, $shippingCarrierCode, $address, $apply = false)
    {
        //DHL API call is not required if DHL option is not enabled from admin
        if (!$this->reachHelper->getDhlEnabled()) {
           return;
        }

        //this can go somewhere else more appropriate
        $countries_require_state = array('CA','BR');

        try {
            $quote = $this->getQuoteById($cartId);
            $quote->collectTotals();
            $duty=0.00;
            $this->_logger->debug('Entered DT routine');
            $this->_logger->debug('current country '.$address->getCountryId());
            $this->_logger->debug('current region '.$address->getRegionCode());
            $this->_logger->debug('previous country '.$this->checkoutSession->getPrevCountry());
            $this->_logger->debug('previous region '.$this->checkoutSession->getPrevRegion());

            if (!$this->allowDuty($address->getCountryId())
                || !$this->allowShipping($shippingMethodCode, $shippingCarrierCode)
            ) {
                $this->handleNoDutyOrShippingCase($duty, $quote);
                return $this->response;
            }

            //Countries (as per DHL doc) where both country name and states are required but are not selected yet.
            //At present those are Canada and Brazil
            if (in_array($address->getCountryId(), $countries_require_state, true)
                && !$address->getRegionCode()
            ) {
                $this->handleStateUnspecifiedCase($duty);
                return $this->response;
            }

            //previous country selection and current country selections are the same
            //this check is necessary as during one order placement this place is entered multiple times due to the way
            // ui event and data binding logic are set in Magento and our extension derived form that
            if (($this->checkoutSession->getPrevCountry() == $address->getCountryId() )
                && ( $address->getCountryId() !='')
            ) {
                $this->_logger->debug('Previous and current country selections are the same');
                $this->response->setIsOptional($this->getIsOptional($address->getCountryId()));
                //if chosen country is one of the special countries where state is needed
                //but state is either not specified
                if (in_array($address->getCountryId(), $countries_require_state, true)) {
                    if ((($this->checkoutSession->getPrevRegion() == $address->getRegionCode())
                        &&  $this->checkoutSession->getPrevRegion() !='')
                        || !$address->getRegionCode()  //this check is redundant here
                    ) {
                        $this->handleCaseWithCountryAndStateSpecified($address, $apply);
                        return $this->response;
                    }
                }
                else {//country selection did not change and
                    //for these countries (other than 'CA' and 'BR') D&T does not changes with change in state
                    //(as per the requirement as of now; we double checked with the business.)
                    $quote = $this->checkoutSession->getQuote();
                    $this->response->setSuccess(true);
                    $this->_logger->debug('country selection did not change and not a special country');
                    $this->handleCaseWhereDutyIsAlreadyRetrieved($apply, $quote, $address);
                    return $this->response;
                }
            }
            //trying to get Duty value by calling DHL Duty API
            $this->callDHLDutyTaxApi($cartId, $address, $shippingCharge, $duty, $apply, $quote);
        } catch (\Exception $e) {
            $this->response->setSuccess(false);
            $this->response->setErrorMessage(
                __('Something went wrong while generating the DHL request: ' . $e->getMessage())
            );
        }

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
            $this->_logger->debug('$allowed ::'.$allowed);

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
                $itemData['itemValue']=['value'=>($item->getRowTotal() - $item->getDiscountAmount())/$item->getQty(),'currency'=>$quote->getQuoteCurrencyCode()];
                $itemData['itemQuantity']=['value'=>$item->getQty(),'unit'=>"PCS"];
                $itemData['countryOfOrigin'] = $this->getCountryOfOrigin($item->getSku());
                if (!$itemData['countryOfOrigin']) {
                    $itemData['countryOfOrigin'] = $request['senderAddress']['country'];
                }
                if ($this->reachHelper->getPrefTariffs() == 1 ) {
                    $itemData['qualifiesForPreferentialTariffs'] = true;
                } else {
                    $itemData['qualifiesForPreferentialTariffs'] = false;
                }
                $request['customsDetails'][]=$itemData;
            }

            $this->_logger->debug(json_encode($request));
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

        $region = $this->reachHelper->getShippingOriginState(self::XML_PATH_ORIGIN_REGION_ID,
            $this->storeManager->getStore()->getId());
        if (is_numeric($region)) {
            $this->regionModel->load($region);
            $region = $this->regionModel->getCode();
        }
        $origin['state']=$region;

        $origin['country']=$this->reachHelper->getShippingOriginCountry(
            self::XML_PATH_ORIGIN_COUNTRY_ID, $this->storeManager->getStore()->getId());

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
        $this->_logger->debug(json_encode($request));
        $response = $rest->executePost(json_encode($request));
        $result = $response->getResponseData();
        $this->_logger->debug(json_encode($result));
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

        if (isset($result['access_token'])) {
            return $result['access_token'];
        }
        return null;
    }
}
