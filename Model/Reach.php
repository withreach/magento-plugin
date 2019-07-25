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

    /**
     * Constructor
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Reach\Payment\Model\Currency $currencyModel
     * @param \Reach\Payment\Helper\Data $hlper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Reach\Payment\Model\Currency $currencyModel,
        \Reach\Payment\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
    ) {
        $this->_coresession     = $session;
        $this->currencyModel    = $currencyModel;
        $this->reachHelper  = $helper;
        $this->checkoutSession  = $checkoutSession;
        $this->httpRestFactory  = $httpRestFactory;
    }

    /**
     * @param string $method
     * @return boolean
     */
    public function isAvailable($method)
    {
        if(!$this->reachHelper->getReachEnabled()) {
            return false;
        }

        $methods = $this->fetchPaymentMethods();

        if ($method == \Reach\Payment\Model\Cc::METHOD_CC && count($methods['Card'])) {
            return true;
        }
        if ($method == \Reach\Payment\Model\Paypal::METHOD_PAYPAL && count($methods['Online'])) {
            $found=false;
            foreach ($methods['Online'] as $onmethod) {
                if ($onmethod['Id'] == 'PAYPAL') {
                    $found=true;
                }
            }
            return $found;
        }
        return false;
    }

    /**
     * @return array
     */
    protected function fetchPaymentMethods()
    {

        $localize = $this->getLocalize();
        $currencyCode = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        if (!$localize || !isset($localize['country'])) {
            return [];
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
