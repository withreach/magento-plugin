<?php

namespace Reach\Payment\Model;

/**
 * PaypalManagement model
 *
 */
class PayPalManagement implements \Reach\Payment\Api\PayPalManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Reach\Payment\Helper\Checkout
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $coreUrl;

    /**
     * @var \Reach\Payment\Api\Data\ResponseInterface
     */
    private $response;

    /**
     * @var \Reach\Payment\Model\Api\HttpTextFactory
     */
    private $httpTextFactory;

    

    /**
     * Constructor
     *
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Reach\Payment\Helper\Checkout $checkoutHelper
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory
     * @param \Reach\Payment\Api\Data\ResponseInterface $response
     */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Reach\Payment\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Framework\UrlInterface $coreUrl,
        \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory,
        \Reach\Payment\Api\Data\ResponseInterface $response
    ) {
        $this->quoteRepository    = $quoteRepository;
        $this->reachHelper    = $reachHelper;
        $this->checkoutSession    = $checkoutSession;
        $this->_customerSession   = $customerSession;
        $this->checkoutHelper     = $checkoutHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->coreUrl            = $coreUrl;
        $this->response           = $response;
        $this->httpTextFactory    = $httpTextFactory;
    }

    /**
     * @inheritDoc
     */
    public function savePaymentAndPlaceOrder($cartId,$deviceFingerprint)
    {
        try {
            $quote = $this->getQuoteById($cartId);
            $quote->collectTotals();
            $quote->reserveOrderId()->save();

            $this->quote = $quote;
            $payment = $quote->getPayment();
            $payment->setMethod(Paypal::METHOD_PAYPAL);

            //save order with pending payment
            $order = $this->checkoutHelper->placeOrder($quote);

            if ($order) {
                $this->checkoutSession->setData("reach_order_pending_payment", $order->getId());
                $payment = $order->getPayment();
                $request = $this->_buildCheckoutRequest($payment,$deviceFingerprint);
                $this->_logger->debug('==========PayPal - savePaymentAndPlaceOrder - BEGIN ==========');
                $this->_logger->debug($request);
                $url = $this->reachHelper->getCheckoutUrl();
                $response = $this->callCurl($url, $request);
                $this->validateResponse($response['response'], $response['signature']);
                $response = json_decode($response['response'], true);
                $this->processErrors($response);
                $this->setTransStatus($payment, $response);
                $data=['action'=>$response['Action']['Redirect']];
                $this->response->setSuccess(true);
                $this->response->setResponse($data);
                $this->_logger->debug('==========PayPal - savePaymentAndPlaceOrder - END ==========');
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
            }
        } catch (\Exception $e) {
            $this->response->setSuccess(false);
            $this->response->setErrorMessage(
                __('Something went wrong while generating the paypal request: ' . $e->getMessage())
            );
        }

        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function getQuoteById($cartId)
    {
        return $this->getQuoteRepository()->get($cartId);
    }

    /**
     * Get quote repository
     *
     * @return \Magento\Quote\Api\CartRepositoryInterface
     */
    public function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    /**
     * Get Quote id mask factory
     *
     * @return \Magento\Quote\Model\QuoteIdMaskFactory
     */
    public function getQuoteIdMaskFactory()
    {
        return $this->quoteIdMaskFactory;
    }

    /**
     * Build request array
     *
     * @param object $payment
     * @return array
     */
    protected function _buildCheckoutRequest($payment,$deviceFingerprint)
    {
        $request=[];
        $order = $payment->getOrder();
        $request['MerchantId'] = $this->reachHelper->getMerchantId();
        $request['ReferenceId'] = $order->getIncrementId();
        $request['ConsumerCurrency']= $order->getOrderCurrencyCode();
        $request['DeviceFingerprint'] = $deviceFingerprint;
        $order->getOrderCurrencyCode();
        

        $request['Items']=[];
        foreach ($order->getAllVisibleItems() as $item) {
            $itemData=[];
            $itemData['Sku'] = $item->getSku();
            $itemData['ConsumerPrice'] = $this->convertCurrency($order->getOrderCurrencyCode(),$item->getPrice());
            $itemData['Quantity'] = $item->getQtyOrdered();
            $request['Items'][]=$itemData;
        }

        $shippingAddress = $order->getShippingAddress();
        if ($payment->getMethodInstance()->getConfigPaymentAction() == 'authorize') {
            $request['Capture'] = false;
        } else {
            $request['Capture'] = true;
        }
        
        $consumer =[
                'Name' => $order->getCustomerName(),
                'Email' => $order->getCustomerEmail(),
                'Phone' => $shippingAddress->getTelephone(),
                'Region' => $shippingAddress->getRegionCode(),
                'Address' => implode(" ", $shippingAddress->getStreet()),
                'City' => $shippingAddress->getCity(),
                'PostalCode' => $shippingAddress->getPostcode(),
                'Country' => $shippingAddress->getCountryId()
                ];
        $request['Consumer'] = $consumer;
        $request['PaymentMethod'] = 'PAYPAL';
        $request['Return'] = $this->getCallbackUrl($order);
        
        $request['Shipping']=[];
        if ($order->getShippingAmount()) {
            $shippingAmount = $this->convertCurrency($order->getOrderCurrencyCode(),$order->getShippingAmount());
            $request['Shipping'][]=['Name'=>'Shipping Cost','ConsumerPrice'=>$shippingAmount];     
        }
        if ($order->getReachDuty()) {
            $request['ShippingRequired'] = true;
            $request['Shipping']['ConsumerDuty']=$this->convertCurrency($order->getOrderCurrencyCode(),$order->getReachDuty());
        } else {
            $request['Shipping']['ConsumerDuty']=0;
        }
        $request['Shipping']['ConsumerPrice']=$this->convertCurrency($order->getOrderCurrencyCode(),$order->getShippingAmount());
        $request['Shipping']['ConsumerTaxes']=$this->convertCurrency($order->getOrderCurrencyCode(),$order->getTaxAmount());
        $request['Consignee']= $this->getConsigneeInfo($order);
        if($order->getDiscountAmount())
        {
            $request['Discounts']=[];
            $discountAmount = $this->convertCurrency($order->getOrderCurrencyCode(),$order->getDiscountAmount() * -1);
            $request['Discounts'][]=['Name'=>$order->getCouponCode()?$order->getCouponCode():'Discount','ConsumerPrice'=>$discountAmount];
        }
        $request['ConsumerTotal']=$this->convertCurrency($order->getOrderCurrencyCode(),$order->getGrandTotal());
        return $request;
    }
    
    /**
     * get order consignee information
     *
     * @param object $order
     * @return array
     */
    protected function getConsigneeInfo($order)
    {
        $shippingAddress = $order->getShippingAddress();
        return [
                'Name' => $order->getCustomerName(),
                'Email' => $order->getCustomerEmail(),
                'Phone' => $shippingAddress->getTelephone(),
                'Region' => $shippingAddress->getRegionCode(),
                'Address' => implode(" ", $shippingAddress->getStreet()),
                'City' => $shippingAddress->getCity(),
                'PostalCode' => $shippingAddress->getPostcode(),
                'Country' => $shippingAddress->getCountryId()
                ];
    }
    
    /**
     * get paypal processing callback url
     *
     * @param object $order
     * @return string
     */
    private function getCallbackUrl($order)
    {
        $url = $this->coreUrl->getUrl('reach/paypal/processing', [
            '_secure' => true,
            '_store'  => $order->getStoreId()
        ]);
        
        $url .= "?quoteid=" . $order->getQuoteId();

        return $url;
    }

    /**
     * validate response
     *
     * @param array $response
     * @param string $nonce
     * @return boolean
     */
    protected function validateResponse($response, $nonce)
    {
        $nonce = str_replace(' ', '+', $nonce);
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $response, $key, true));
        return $signature == $nonce;
    }

    /**
     * If response is failed throw exception
     *
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processErrors($response)
    {

        if (isset($response['Error']) && count($response['Error'])) {
            $errorMessage = $response['Error']['Code'];
            if ($response['Error']['Message'] != '') {
                $errorMessage = ':'.$response['Error']['Message'];
            }
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errorMessage)
            );
        }
    }

    /**
     * Execute API request
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @return return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function callCurl($url, $params, $method = "POST")
    {
        $json = json_encode($params);
        $secret = $this->reachHelper->getSecret();
        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));

        $rest = $this->httpTextFactory->create();
        $rest->setContentType("application/x-www-form-urlencoded");
        $rest->setUrl($url);
        $result = $rest->executePost('request='.urlencode($json).'&signature='.urlencode($signature));
        $responseString = $result->getResponseData();
        $response =[];
        parse_str($responseString, $response);
        return $response;
    }

    /**
     * @param DataObject $payment
     * @param DataObject $response
     * @return Object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setTransStatus($payment, $response)
    {

        if (isset($response['OrderId']) && isset($response['Action'])) {
            $payment->setTransactionId($response['OrderId']);

            //as magento allows to store only 32 character for transaction id, removing - to store it
            $trn = str_replace('-', '', $response['OrderId']);
            $payment->setLastTransId($trn);
            $payment->setAdditionalInformation('Action', $response['Action']);
            $payment->setAdditionalInformation('OrderId', $response['OrderId']);
            
            if (isset($response['Expiry'])) {
                $payment->setAdditionalInformation('Expiry', $response['Expiry']);
            }
            $payment->save();
        }
    }

    /**
    * Convert decimal to int for JPY 
    *
    * @param string $currencycode
    * @param float $amount
    * @return int|float
    */
    protected function convertCurrency($currencycode,$amount)
    {
        if($currencycode == "JPY")
        {
            return round($amount);
        }
        return $amount;
    }
}
