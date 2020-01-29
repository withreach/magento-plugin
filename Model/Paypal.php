<?php

namespace Reach\Payment\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Formatter;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\ConfigInterfaceFactory;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Paypal\Model\Payflow\Service\Response\Handler\HandlerInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Paypal payment model
 *
 */
class Paypal extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_PAYPAL = 'reach_paypal';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'reach_paypal';
    
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

     /**
      * Availability option
      *
      * @var bool
      */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    private $isInitializeNeeded = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Reach\Payment\Model\Reach
     */
    protected $reachPayment;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $coreUrl;

    /**
     * @var \Reach\Payment\Model\Api\HttpTextFactory
     */
    private $httpTextFactory;

    /**
     * @var \Reach\Payment\Model\Currency
     */
    protected $reachCurrency;

    /**
     * Paypal constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\Reach $reachPayment
     * @param \Reach\Payment\Model\Currency $reachCurrency
     * @param \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Reach $reachPayment,
        \Reach\Payment\Model\Currency $reachCurrency,
        \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->reachCurrency     = $reachCurrency;
        $this->storeManager     = $storeManager;
        $this->reachHelper = $reachHelper;
        $this->reachPayment      = $reachPayment;
        $this->coreUrl   = $coreUrl;
        $this->httpTextFactory    = $httpTextFactory;
        $this->transactionModel = $transactionModel;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function initialize($paymentAction, $stateObject)
    {        
        $payment = $this->getInfoInstance();
        $order   = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }
 
    /**
     * Capture payment
     *
     * @param InfoInterface|Payment|Object $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($payment->getParentTransactionId()) {
            $request=[];
            $request['MerchantId'] = $this->reachHelper->getMerchantId();
            $request['OrderId'] = $payment->getParentTransactionId();
            $url = $this->reachHelper->getCaptureUrl();

            $response = $this->callCurl($url,$request);
            $this->validateResponse($response['response'],$response['signature']);
            $response = json_decode($response['response'],true);
        
            $this->processErrors($response);
            $this->setTransStatus($payment, $response,true);
        }
        return $this;       
    }

    /**
     * Void payment
     *
     * @param InfoInterface|Payment|Object $payment
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $request=[];
        $request['OrderId'] = $payment->getParentTransactionId();
        $request['MerchantId']= $this->reachHelper->getMerchantId();
        $url = $this->reachHelper->getCancelUrl();
        $response = $this->callCurl($url,$request);         
        if(!$this->validateResponse($response['response'],$response['signature']))
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cancel response not validated')
            );
        }
        $response = json_decode($response['response'],true);
        if(isset($response['OrderId']))
        {
            $this->processErrors($response);
            $payment->setTransactionId(
                    $response['OrderId']
                )->setIsTransactionClosed(
                    1
                )->setShouldCloseParentTransaction(
                    1
                );
                            
        }
        else{
            throw new \Exception("Error during canceling authorization");
        }
        return $this;
    }

    /**
     * Check void availability
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function canVoid()
    {
        if ($this->getInfoInstance()->getAmountPaid()) {
            $this->_canVoid = false;
        }

        return $this->_canVoid;
    }

    /**
     * Attempt to void the authorization on cancelling
     *
     * @param InfoInterface|Object $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        if (!$payment->getOrder()->getInvoiceCollection()->count()) {
            return $this->void($payment);
        }

        return false;
    }

     /**
     * Refund capture
     *
     * @param InfoInterface|Payment|Object $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $request=[];
        $request['OrderId'] = str_replace('-capture','',$payment->getParentTransactionId());
        $request['MerchantId']= $this->reachHelper->getMerchantId();
        $request['Amount']= $amount;
        $request['ReferenceId']=$payment->getParentTransactionId();
        $request['ReferenceId']=$this->getReferenceIdForRefund($payment);
        $url = $this->reachHelper->getRefundUrl();
        $response = $this->callCurl($url,$request);

        if(isset($response['response']) && isset($response['signature']))
        {
            $this->validateResponse($response['response'],$response['signature']);
            $response = json_decode($response['response'],true);
        }
        else{
            throw new \Exception("Error during refund payment");
        }

        $this->processErrors($response);
        if (isset($response['RefundId'])) {
            $payment->setTransactionId($response['RefundId'])->setIsTransactionClosed(true);
        }
        return $this;
    }

    /**
     * Get last refund or capture transaction id
     *
     * @param InfoInterface|Payment|Object $payment
     * @return $string
     */
    protected function getReferenceIdForRefund($payment)
    {
        $collection = $this->transactionModel->getCollection();
        $collection->addOrderIdFilter($payment->getOrder()->getId());
        $collection->addTxnTypeFilter(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $collection->setOrder('transaction_id','DESC');
        if($collection->count() && $collection->getFirstItem()->getTxnId())
        {
            return $collection->getFirstItem()->getTxnId();
        }
        return $payment->getParentTransactionId();
    }

      /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $this->_logger->debug('---------------- isAvailable - START OF REQUEST----------------');
        $this->_logger->debug('PayPal Enabled: ');
        $this->_logger->debug($this->reachHelper->getReachEnabled());
        if(!$this->reachHelper->getReachEnabled())
        {
            return false;
        } 
        $path = 'payment/'.self::METHOD_PAYPAL . '/active';
        $this->_logger->debug('Reach is Enabled!');

        $this->_logger->debug('Path:');
        $this->_logger->debug(json_encode($path));

        $this->_logger->debug('Calc 1:');
        $this->_logger->debug(json_encode($this->reachPayment->isAvailable(self::METHOD_PAYPAL)));

        $this->_logger->debug('Calc 2:');
        $this->_logger->debug(json_encode($this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId())));

        $this->_logger->debug('methods:');
        $this->_logger->debug(json_encode($this->reachPayment->testMethods()));

        $this->_logger->debug('testCurrencyCode:');
        $this->_logger->debug(json_encode($this->reachPayment->testCurrencyCode()));

        $this->_logger->debug('testLocalize:');
        $this->_logger->debug(json_encode($this->reachPayment->testLocalize()));

        $this->_logger->debug('testreachmethods:');
        $this->_logger->debug(json_encode($this->reachPayment->testreachmethods()));

        $this->_logger->debug('testGetLocalize:');
        $this->_logger->debug(json_encode($this->reachPayment->testGetLocalize()));

        $this->_logger->debug('testLocalizeCurrency:');
        $this->_logger->debug(json_encode($this->reachPayment->testLocalizeCurrency()));

        $this->_logger->debug('---------------- isAvailable - END OF REQUEST----------------');
       // return $this->reachPayment->isAvailable(self::METHOD_PAYPAL) && $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
       return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
    }

     /**
     * Flag if we need to run payment initialize while order place
     *
     * @return bool
     * @api
     */
    public function isInitializeNeeded()
    {
        return $this->isInitializeNeeded;
    }

    /**
     * Set initialized flag to capture payment
     */
    public function markAsInitialized()
    {
        $this->isInitializeNeeded = false;
    }

    /**
    * validate response
    *
    * @param array $response
    * @param string $nonce
    * @return boolean    
    */    
    protected function validateResponse($response,$nonce)
    {

        $nonce = str_replace(' ','+',$nonce);
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $response, $key, TRUE));
        return $signature == $nonce;    
    }

    /**
    * Build request array
    *
    * @param object $payment    
    * @return array   
    */
    protected function _buildCheckoutRequest($payment, $amount)
    {
        $request=[];
        $order = $payment->getOrder();
        $info = $this->getInfoInstance();        

        $order = $payment->getOrder();
        $request['MerchantId'] = $this->reachHelper->getMerchantId();
        $request['ReferenceId'] = $order->getIncrementId();
        $request['ConsumerCurrency']= $order->getOrderCurrencyCode();
        $order->getOrderCurrencyCode();
    
        $rateOfferId =  $this->reachCurrency->getOfferId($order->getOrderCurrencyCode());
        if(!empty($rateOfferId)) {
            $request['RateOfferId'] = $rateOfferId;
        }

        $request['Items']=[];
        foreach ($order->getAllVisibleItems() as $item)
        {
            $itemData=[];
            $itemData['Sku'] = $item->getSku();
            $itemData['ConsumerPrice'] = $item->getPrice();
            $itemData['Quantity'] = $item->getQtyOrdered();
            $request['Items'][]=$itemData;         
        }

        $shippingAddress = $order->getShippingAddress();
        $consumer = $this->getConsumerInfo($order);
        $request['Consumer'] = $consumer;
        $request['ViaAgent']=true;
        
        $request['ShippingRequired'] = false;
        $request['Consignee']= $this->getConsigneeInfo($order);
        $request['ShippingRequired'] = false;
        
        if($order->getReachDuty())
        {   
            $request['ShippingRequired'] = true;
        }
        $request['Shipping']=[];
        $request['Shipping']['ConsumerPrice']=$order->getShippingAmount();
        $request['Shipping']['ConsumerTaxes']=$order->getTaxAmount();
        $request['Shipping']['ConsumerDuty']=$order->getReachDuty();
        $request['Consignee']= $this->getConsigneeInfo($order);
        if($order->getDiscountAmount())
        {
            $request['Discounts']=['Name'=>$order->getCouponCode()?$order->getCouponCode():'Discount','ConsumerPrice'=>$order->getDiscountAmount() * -1];
        }
        $request['ConsumerTotal']=$order->getGrandTotal();


        $request['PaymentMethod'] = 'PAYPAL';
        $request['Return'] = $this->getCallbackUrl($order);
        $this->_logger->debug('---------------- _buildCheckoutRequest - START OF REQUEST----------------');
        $this->_logger->debug($request);
        $this->_logger->debug('---------------- _buildCheckoutRequest - END OF REQUEST----------------');

        return $request;
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
    * get consumer info
    *
    * @param object $order    
    * @return array   
    */  
    protected function getConsumerInfo($order)
    {
        $billingAddress = $order->getBillingAddress();
        return [
            'Name' => $order->getCustomerName(), 
            'Email' => $order->getCustomerEmail(), 
            'Phone' => $billingAddress->getTelephone(), 
            'Region' => $billingAddress->getRegionCode(), 
            'Address' => implode(" ", $billingAddress->getStreet()), 
            'City' => $billingAddress->getCity(), 
            'PostalCode' => $billingAddress->getPostcode(), 
            'Country' => $billingAddress->getCountryId()
            ];
    }


    /**
     * If response is failed throw exception
     *
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function processErrors($response)
    {

        if(isset($response['Error']) && count($response['Error']))
        {
            $errorMessage = $response['Error']['Code'];
            if(isset($response['Error']['Message']) && $response['Error']['Message'] != '')
            {
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
    protected function callCurl($url,$params,$method="POST")
    {        
        $json = json_encode($params);
        $secret = $this->reachHelper->getSecret();
        $signature = base64_encode(hash_hmac('sha256', $json,$secret, TRUE));

        $rest = $this->httpTextFactory->create();
        $rest->setContentType("application/x-www-form-urlencoded");
        $rest->setUrl($url);
        $result = $rest->executePost('request='.urlencode($json).'&signature='.urlencode($signature));
        $responseString = $result->getResponseData();    
        $response =[];
        parse_str($responseString,$response);
        return $response;
    }

    /**
     * @param DataObject $payment
     * @param DataObject $response
     *
     * @return Object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setTransStatus($payment, $response)
    {
        
        if($response['Completed']===false)
        {
            $payment->setIsTransactionPending(true);
        }
    }

}   