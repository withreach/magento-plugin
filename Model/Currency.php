<?php

namespace Reach\Payment\Model;

class Currency extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Reach\Payment\Model\Api\HttpRestFactory
     */
    protected $httpRestFactory;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $addressService;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    const PRECISION = 2;
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\ResourceModel\Currency $resource
     * @param \Reach\Payment\Model\ResourceModel\Currency\Collection $collection
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data = []
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\ResourceModel\Currency $resource,
        \Reach\Payment\Model\ResourceModel\Currency\Collection $collection,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {

        $this->reachHelper = $reachHelper;
        $this->httpRestFactory = $httpRestFactory;
        $this->addressService  = $addressService;
        $this->_logger = $logger;
        parent::__construct($context, $registry, $resource, $collection, $data);
    }

    /**
     * Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Reach\Payment\Model\ResourceModel\Currency::class);
    }

     /**
      * Get reach currencies
      *
      * @return array
      */
    public function getReachCurrencies()
    {
        $collection = $this->getCollection();
        if (!$collection->count()) {
            $this->updateRates();
            $collection = $this->getCollection();
        }
        $codes = [];
        foreach ($collection as $rate) {
            $codes[]=$rate['currency'];
        }

        return $codes;
    }

    /**
     * Get reach currencies rates
     *
     * @return array
     */
    public function getReachCurrencyRates()
    {
        $rates=[];
        foreach ($this->getCollection() as $rate) {
            $rates[$rate['currency']]=$rate['rate'];
        }
        return $rates;
    }
    
    /**
     * Update reach currencies rates
     *
     * @return this
     */
    public function updateRates()
    {
        $rates = $this->fetchRates();
        if ($rates && count($rates) > 0) {
            $receviedRates = [];
            foreach ($rates as $rate) {
                $data = $this->getResource()->getByCurrency($rate['Currency']);
                if (count($data) && isset($data[0]['rate_id'])) {
                    //update
                    $this->setData([
                        'rate_id'=>$data[0]['rate_id'],
                        'offer_id'=>$rate['Id'],
                        'currency'=>$rate['Currency'],
                        'rate'=>$rate['Rate'],
                        'expire_at'=>$rate['Expiry']
                    ])->save();
                    $this->storedData = []; //this is one work around for this Magento bug
                    // (https://github.com/magento/magento2/issues/4174) as explained in JIRA MAG-102
                } else {
                    //insert
                    $this->setData([
                        'rate_id'=>null,
                        'offer_id'=>$rate['Id'],
                        'currency'=>$rate['Currency'],
                        'rate'=>$rate['Rate'],
                        'expire_at'=>$rate['Expiry']
                    ])->save();
                }
                $receviedRates[]=$rate['Currency'];
            }
            $this->getResource()->removeOldRates($receviedRates);
        }
    }

    /**
     * Get localization
     *
     * @return array
     */
    public function getLocalizeCurrency()
    {
        $rest = $this->httpRestFactory->create();
        $url = $this->reachHelper->getApiUrl();
        $url = $url.'localize?MerchantId='.$this->reachHelper->getMerchantId();
        $ip = $this->getConsumerIp();
        if (!$this->checkLocalIP($ip)) {
            $url = $url.'&ConsumerIpAddress='.$ip;
        }
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();
        
        if (isset($result['Currency'])) {
            return [
                'currency'=>$result['Currency'],
                'symbol'=>$result['Symbol'],
                'country'=>$result['Country']
            ];
        }
        return false;
    }

     /**
      * Get Offer id
      *
      * @param string $currencyCode
      * @return float|null
      */
    public function getOfferId($currencyCode)
    {
        $data = $this->getResource()->getByCurrency($currencyCode);
        if (count($data) && isset($data[0]['rate_id'])) {
            return $data[0]['offer_id'];
        }
        return null;
    }

    /**
     * Fetch reach currencies
     *
     * @return array|null
     */
    protected function fetchRates()
    {
        $this->_logger->debug('---------------- fetchRates - START OF REQUEST----------------');
        $rest = $this->httpRestFactory->create();
        $url = $this->reachHelper->getApiUrl();
        $url.='getRates?MerchantId='.$this->reachHelper->getMerchantId();
        $this->_logger->debug(json_encode($url));
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();
        if (isset($result['RateOffers'])) {
            return $result['RateOffers'];
        }
        $this->_logger->debug('---------------- fetchRates - END OF REQUEST----------------');
        return null;
    }

    /**
     * Get consumer IP
     *
     * @return string
     */
    protected function getConsumerIp()
    {
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
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
        {
            // is a local ip address
            $this->_logger->debug('Using a local IP address');
            return true;
        }
        $this->_logger->debug('Using a public IP address');
        return false;
    }


    /**
     * Convert decimal to int for JPY
     *
     * @param string $currencycode
     * @param float $amount
     * @return int|float
     */
    public function convertCurrency($currencycode,$amount)
    {
        if($currencycode == "JPY")
        {
            return round($amount);
        }
        return round($amount, self::PRECISION);
    }
}
