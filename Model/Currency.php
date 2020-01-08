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
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $addressService
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\ResourceModel\Currency $resource
     * @param \Reach\Payment\Model\ResourceModel\Currency\Collection $collection
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
        array $data = []
    ) {

        $this->reachHelper = $reachHelper;
        $this->httpRestFactory = $httpRestFactory;
        $this->addressService  = $addressService;
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
                    $this->setData([
                        'rate_id'=>$data[0]['rate_id'],
                        'offer_id'=>$rate['Id'],
                        'currency'=>$rate['Currency'],
                        'rate'=>$rate['Rate'],
                        'expire_at'=>$rate['Expiry']
                    ])->save();
                } else {
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
        $rest = $this->httpRestFactory->create();
        $url = $this->reachHelper->getApiUrl();
        $url.='getRates?MerchantId='.$this->reachHelper->getMerchantId();
        $rest->setUrl($url);
        $response = $rest->executeGet();
        $result = $response->getResponseData();
        if (isset($result['RateOffers'])) {
            return $result['RateOffers'];
        }

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
     * Check IP is local ip
     *
     * When running in Docker, the IP addresses assigned by the Docker service
     * must be added here in order for the application to run. Error displayed
     * is regarding Line 71 in Model/Reach.php.
     *
     * @return boolean
     */
    protected function checkLocalIP($ip)
    {
        return in_array($ip, ['localhost','127.0.0.1','172.19.0.1','172.25.0.7','172.25.0.1']);
    }
}
