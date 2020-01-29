<?php

namespace Reach\Payment\Model;

/**
 * Reach model
 *
 */
class Reach
{
    /**
     * @var  \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coresession;

    /**
     * @var  \Reach\Payment\Model\Currency
     */
    protected $currencyModel;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $httpRestFactory;

    protected $_logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Reach\Payment\Model\Currency $currencyModel
     * @param \Reach\Payment\Helper\Data $hlper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Magento\Payment\Model\Method\Logger $logger
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Reach\Payment\Model\Currency $currencyModel,
        \Reach\Payment\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Magento\Payment\Model\Method\Logger $logger
    ) {
        $this->_coresession     = $session;
        $this->currencyModel    = $currencyModel;
        $this->reachHelper  = $helper;
        $this->checkoutSession  = $checkoutSession;
        $this->httpRestFactory  = $httpRestFactory;
        
    }

    public function testMethods(){
        $methods = $this->fetchPaymentMethods();
        return $methods;
    }

    public function testCurrencyCode() {
        $currencyCode = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        return $currencyCode;
    }

    public function testLocalize() {
        $localize = $this->getLocalize();
        return $localize;
    }

    public function testReachMethods() {
        return $this->checkoutSession->getReachMethods();
    }

    /**
     * @param string $method
     * @return boolean
     */
    public function isAvailable($method)
    {
        $available = false;
        if($this->reachHelper->getReachEnabled()) {
            $methods = $this->fetchPaymentMethods();
            if (array_key_exists('Card', $methods) && $method == \Reach\Payment\Model\Cc::METHOD_CC) {
                $available = true;
            }
            if (array_key_exists('Online', $methods) && $method == 'payment/reach_paypal/active') {
                if (array_key_exists('Online', $methods)) {
                    foreach ($methods['Online'] as $onmethod) {
                        if ($onmethod['Id'] == 'PAYPAL') {
                            $available = true;
                        }
                    }
                }
            }
        }
        return $available;
    }
		

    /**
     * @return array
     */
    protected function fetchPaymentMethods()
    {

        $localize = $this->getLocalize();
        $currencyCode = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        if (!$localize || !isset($localize['country'])) {
            return ['no localization'];
        }

        $sessionMethods=[];
        if ($this->checkoutSession->getReachMethods()!==null) {
            $sessionMethods=$this->checkoutSession->getReachMethods();
        }

        if (isset($sessionMethods[$localize['country'].'_'.$currencyCode])) {
            return $sessionMethods[$localize['country'].'_'.$currencyCode];
        }

        $rest = $this->httpRestFactory->create();
        $url = $this->reachHelper->getApiUrl();
        $url.='getPaymentMethods?MerchantId='.$this->reachHelper->getMerchantId();
        $url.='&Currency='.$currencyCode;
        if ($localize && isset($localize['country'])) {
            $url.='&Country='.$localize['country'];
        } else {
            return [];
        }
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();
        
        $methods=[];
        if (isset($result['PaymentMethods']) && count($result['PaymentMethods'])) {
            foreach ($result['PaymentMethods'] as $method) {
                if (!isset($methods[$method['Class']])) {
                    $methods[$method['Class']] = [];
                }
                $methods[$method['Class']][] = $method;
            }
        }
        $this->checkoutSession->setReachMethods([$localize['country'].'_'.$currencyCode=>$methods]);
        return $methods;
    }

    public function testGetLocalize() {
        return $this->_coresession->getLocalize();
    }

    public function testLocalizeCurrency() {
        $localize = $this->currencyModel->getLocalizeCurrency();
        return $localize;
    }

    /**
     * Get localized currency array
     *
     * @return array
     */
    protected function getLocalize()
    {
        $localize = null;
        if ($this->_coresession->getLocalize() !== null) {
            $localize = $this->_coresession->getLocalize();
        } else {
            $localize = $this->currencyModel->getLocalizeCurrency();
        }
        return $localize;
    }
}
