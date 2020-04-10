<?php

namespace Reach\Payment\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
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
use Magento\Framework\App\ObjectManager;

/**
 * Cc payment model
 *
 */
class Cc extends \Magento\Payment\Model\Method\Cc
{


    const METHOD_CC = 'reach_cc';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::METHOD_CC;


    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;


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
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Gateway request timeout
     *
     * @var int
     */
    protected $_clientTimeout = 45;


    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = true;
    
        /**
         * @var \Magento\Store\Model\StoreManagerInterface
         */
    protected $storeManager;

    /**
     * @var ConfigInterfaceFactory
     */
    protected $configFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * @var HandlerInterface
     */
    private $errorHandler;
    
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
     * @var \Reach\Payment\Model\Currency
     */
    protected $reachCurrency;

    /**
     * @var \Reach\Payment\Model\Api\HttpTextFactory
     */
    private $httpTextFactory;

    /**
     * @var \Magento\Payment\Helper\CcHelper
     */
     private $ccHelper;
        /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ConfigInterfaceFactory $configFactory
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Helper\CcHelper $cchHelper
     * @param \Reach\Payment\Model\Currency $reachCurrency
     * @param \Reach\Payment\Model\Reach $reachPayment
     * @param \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param Gateway $gateway
     * @param HandlerInterface $errorHandler
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ConfigInterfaceFactory $configFactory,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Helper\CcHelper $ccHelper,
        \Reach\Payment\Model\Currency $reachCurrency,
        \Reach\Payment\Model\Reach $reachPayment,
        \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory,
        \Magento\Framework\UrlInterface $coreUrl,
        HandlerInterface $errorHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager     = $storeManager;
        $this->configFactory    = $configFactory;
        $this->reachHelper  = $reachHelper;
        $this->coreUrl          = $coreUrl;
        $this->reachCurrency     = $reachCurrency;
        $this->reachPayment      = $reachPayment;
        $this->httpTextFactory  = $httpTextFactory;
        $this->ccHelper = $ccHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->errorHandler = $errorHandler;
    }

    /** Why do we need both isAvailable and isActive
     * methods? Should not the first one be enough?
     **/

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
         if(!$this->reachHelper->getReachEnabled())
        {
            return false;
        } 
        $path = 'payment/'.self::METHOD_CC . '/active';
        $isCcActive = $this->reachHelper->getCreditCardActive($path, $this->storeManager->getStore()->getId());
        return $this->reachPayment->isAvailable(self::METHOD_CC) && $isCcActive;
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        $path = 'payment/' . self::METHOD_CC . '/active';
        $isCcActive = $this->reachHelper->getCreditCardActive($path, $storeId);
        return (bool)(int) $isCcActive;
    }
    

    /**
     * Do not validate payment form using server methods
     *
     * @return bool
     */
    public function validate()
    {
        return true;
    }

     /**
      * Authorize payment
      *
      * @param InfoInterface|Payment|Object $payment
      * @param float $amount
      * @return $this
      * @throws \Magento\Framework\Exception\LocalizedException
      * @throws \Magento\Framework\Exception\State\InvalidTransitionException
      */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $request = $this->ccHelper->_buildCheckoutRequest($payment, $amount,  $this->reachHelper,  $this->getInfoInstance(), $this->reachCurrency);
        $request['Capture'] = false;
        $url = $this->reachHelper->getCheckoutUrl();
        $response = $this->callCurl($url, $request);
        $this->_logger->debug(json_encode($url));
        $this->_logger->debug(json_encode($request));
        $this->_logger->debug(json_encode($response));

        if (!isset($response['response']) || !$this->ccHelper->validateResponse($response['response'], $response['signature'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This payment method is not working at the moment, please try another payment option or try again later')
            );
        }
        $response = json_decode($response['response'], true);
        $this->ccHelper->processErrors($response);
        $this->ccHelper->setTransStatus($payment, $response);
        return $this;
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
            $request['MerchantId'] = $this->reachHelper->getMerchantId();
            $request['OrderId'] = $payment->getParentTransactionId();
            $url = $this->reachHelper->getCaptureUrl();
        } else {
            $request = $this->_buildCheckoutRequest($payment, $amount);
            $request['Capture'] = true;
            $url = $this->reachHelper->getCheckoutUrl();
        }
        $response = $this->callCurl($url, $request);
        if(!$this->validateResponse($response['response'],$response['signature']))
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Capture response not validated')
            );
        }
        $response = json_decode($response['response'], true);
        $this->processErrors($response);
        $this->setTransStatus($payment, $response, true);
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
        $response = $this->callCurl($url, $request);
        
        if(!$this->validateResponse($response['response'],$response['signature']))
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cancel response not validated')
            );
        }

        $response = json_decode($response['response'], true);
        if (isset($response['OrderId'])) {
            $this->processErrors($response);
            $payment->setTransactionId(
                $response['OrderId']. '-cancel'
            )->setIsTransactionClosed(
                1
            )->setShouldCloseParentTransaction(
                1
            );
        } else {
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
        $request['OrderId'] = str_replace('-capture', '', $payment->getParentTransactionId());
        $request['MerchantId']= $this->reachHelper->getMerchantId();
        $request['Amount']= $amount;
        $request['ReferenceId']=$this->ccHelper->getReferenceIdForRefund($payment);
        $url = $this->reachHelper->getRefundUrl();
        $response = $this->callCurl($url, $request);
        
        if(!$this->validateResponse($response['response'],$response['signature']))
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Refund response not validated')
            );
        }
        $response = json_decode($response['response'], true);
        $this->processErrors($response);
        if (isset($response['RefundId'])) {
            $payment->setTransactionId($response['RefundId'])->setIsTransactionClosed(true);
        }
        return $this;
    }






    /**
     * Get method name based on card used
     *
     * @param string $type
     * @return string
     */
    protected function getMethodName($type)
    {
        $method='';
        switch ($type) {
            case 'MC':
                $method='MC';
                break;
            case 'DN':
                $method='DINERS';
            case 'DI':
                $method='DISC';
            case 'AE':
                $method='AMEX';
                break;
            case 'JCB':
                $method='JCB';
            case 'VI':
                $method='VISA';
                break;
            case 'MI':
                $method='MAESTRO';
            case 'EL':
                $method='ELECTRON';
            default:
                break;
        }
        return $method;
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

        $this->_logger->debug('$params: ');
        $this->_logger->debug(json_encode($params));

        $secret = $this->reachHelper->getSecret();

        $this->_logger->debug('$secret: ');
        $this->_logger->debug(json_encode($secret));

        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));

        $this->_logger->debug('$signature: ');
        $this->_logger->debug(json_encode($signature));

        $rest = $this->httpTextFactory->create();
        $rest->setContentType("application/x-www-form-urlencoded");
        $rest->setUrl($url);

        $this->_logger->debug("body :::".'request='.urlencode($json).'&signature='.urlencode($signature));

        $result = $rest->executePost('request='.urlencode($json).'&signature='.urlencode($signature));

        $this->_logger->debug("result ".json_encode($result));
        $responseString = $result->getResponseData();

        $this->_logger->debug("responseString :::".json_encode($responseString));

        $response =[];
        parse_str($responseString, $response);

        return $response;
    }




     /**
      * Whether this method can accept or deny payment
      * @return bool
      * @api
      * @deprecated 100.2.0
      */
    public function canReviewPayment()
    {
        return $this->_canReviewPayment;
    }

    /**
     * Attempt to accept a payment that us under review
     *
     * @param InfoInterface $payment
     * @return false
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function acceptPayment(InfoInterface $payment)
    {
        return $this->canReviewPayment();
    }

     /**
    * Assign data to info model instance
    *
    * @param \Magento\Framework\DataObject|mixed $data
    * @return $this
    * @throws \Magento\Framework\Exception\LocalizedException
    */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $info = $this->getInfoInstance();
        if ($data->getAdditionalData('stash_id')) {
            $info->setAdditionalInformation("stash_id", $data->getAdditionalData('stash_id'));
        }
        if ($data->getAdditionalData('device_fingerprint')) {
            $info->setAdditionalInformation("device_fingerprint", $data->getAdditionalData('device_fingerprint'));
        }

        if ($data->getAdditionalData('oc_selected')) {
            $info->setAdditionalInformation("oc_selected", $data->getAdditionalData('oc_selected'));
        } else {
            $info->setAdditionalInformation("oc_selected", 0);
        }

        if ($data->getAdditionalData('contract_id')) {
            $info->setAdditionalInformation("contract_id", $data->getAdditionalData('contract_id'));
        } else {
            $info->setAdditionalInformation("contract_id", 0);
        }
        
        if ($data->getAdditionalData('cc_last_4')) {
            $info->setData("cc_last_4", $data->getAdditionalData('cc_last_4'));
        }

        return $this;
    }

}
