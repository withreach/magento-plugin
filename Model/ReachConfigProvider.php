<?php

namespace Reach\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * ReachConfigProvider model
 *
 */
class ReachConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Reach\Payment\Helper\Misc
     */
    protected $miscHelper;

    /**
     * @var  \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;


    /**
     * @var  \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $addressService;

    /**
     * @var  \Reach\Payment\Model\Api\HttpRestFactory
     */
    protected $httpRestFactory;


     /**
      * @param \Reach\Payment\Helper\Data $reachHelper
      * @param \Magento\Checkout\Model\Session $checkoutSession
      * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService
      * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
      * @param \Reach\Payment\Helper\Misc $miscHelper
      */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Reach\Payment\Helper\Misc $miscHelper
    ) {
        $this->reachHelper = $reachHelper;
        $this->checkoutSession = $checkoutSession;
        $this->addressService  = $addressService;
        $this->httpRestFactory = $httpRestFactory;
        $this->miscHelper = $miscHelper;
    }

     /**
      * {@inheritdoc}
      */
    public function getConfig()
    {
        $config = [];
        $config['reach']=[];
        $config['reach']['enabled'] =  $this->reachHelper->getReachEnabled();
        $config['reach']['dhl_enabled'] =  $this->reachHelper->getDhlEnabled();
        $config['reach']['badge']=$this->getBadge();
        $config['reach']['dhl_quote']=$this->getDhlQuote();
        $config['reach']['fingerprint_url']=$this->getFingerPrintUrl();
        $config['reach']['dhl_label']=$this->reachHelper->getDhlDutyLabel();
        $config['reach']['stash_url']=$this->reachHelper->getStashApiUrl();
        return $config;
    }

    /**
     * get applied dhl duty value
     * @return float
     */
    protected function getDhlQuote()
    {
        $quote = $this->checkoutSession->getQuote();
        $duty = $quote->getReachDuty();
        return $duty;
    }

    /**
     * Retrive badge image url
     * @return array
     */
    protected function getBadge()
    {
        
        $rest = $this->httpRestFactory->create();
        $url = $this->reachHelper->getApiUrl();
        $url = $url.'badge?MerchantId='.$this->reachHelper->getMerchantId();
        $ip = $this->getConsumerIp();
        if (!$this->miscHelper->checkLocalIP($ip)) {
            $url = $url.'&ConsumerIpAddress='.$ip;
        }
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();
        
        $data=[];
        if (isset($result['Text'])) {
            $data['Text'] =$result['Text'];
        }

        if (isset($result['ImageUrl'])) {
            $data['ImageUrl'] =$result['ImageUrl'];
        }

        if (isset($result['TermsOfServiceUrl'])) {
            $data['TermsOfServiceUrl'] =$result['TermsOfServiceUrl'];
        }

        return $data;
    }

    /**
     * get consumer IP address
     * @return string
     */
    protected function getConsumerIp()
    {
        //return '31.185.95.255';//NOK
        //return '158.69.25.151' ;// CAD
        $ip =  $this->addressService->getRemoteAddress();
        return $ip;
    }

     /**
     * Get fingerprint url
     *
     * @return string     
     */
    protected function getFingerPrintUrl()
    {
        return $this->reachHelper->getApiUrl()."fingerprint?MerchantId=".$this->reachHelper->getMerchantId();
    }
}
