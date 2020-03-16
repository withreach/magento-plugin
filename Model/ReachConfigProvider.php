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
      */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
    ) {
        $this->reachHelper = $reachHelper;
        $this->checkoutSession = $checkoutSession;
        $this->addressService  = $addressService;
        $this->httpRestFactory = $httpRestFactory;
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
        if (!$this->checkLocalIP($ip)) {
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
     * check IP is local machine IP
     * @param  string $ip
     * @return boolean
     */
    protected function checkLocalIP($ip)
    {
        //Ryan's code from MAG-75 (to be able to test payment code properly).
        //In future this method should be moved to some util or helper file to increase reusability.
        //I noticed duplication of this method/code in our extension.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
        {
            // is a local ip address
            return true;
        }
        return false;
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
