<?php

namespace Reach\Payment\Model;


class Currency extends \Magento\Framework\Model\AbstractModel
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
    public $_logger;

    const PRECISION_CUTOFF = 2;

    /**
     * @var \Reach\Payment\Helper\Cacher
     */
    protected $cacher;

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
     * @param \Reach\Payment\Helper\Cacher $cacher
     * @param \Reach\Payment\Helper\Misc $miscHelper
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
        \Reach\Payment\Helper\Cacher $cacher,
        \Reach\Payment\Helper\Misc $miscHelper,
        array $data = []
    ) {

        $this->reachHelper = $reachHelper;
        $this->httpRestFactory = $httpRestFactory;
        $this->addressService  = $addressService;
        $this->_logger = $logger;
        $this->cacher = $cacher;
        $this->miscHelper = $miscHelper;
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
        if (!$this->miscHelper->checkLocalIP($ip)) {
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
     * Convert decimal to int for JPY and for other currencies adjust number of digits after decimal the point based on
     * what is acceptable in those currencies
     * looking for the precision in cache, if not there then look for it in the extension specific database
     * if not found in database then make a call to REACH api
     * once the value is found in api it gets cached in the database; if found in database but not in cache
     * then it is added in the cache (magento style of caching without using third party software component
     * like redis, varnish)
     * @param string $currencyCode
     * @param float $amount
     * @return int|float
     */
    public function convertCurrency($currencyCode, $amount)
    {
        $precision_adjusted = 0;
        $precision = 0;
        if (isset($currencyCode)) {
            $reachCache = $this->cacher->loadDataFromCache();
            $this->_logger->debug("this is in cache :::" . json_encode($reachCache));
            if (isset($reachCache[$currencyCode])) {
                $this->_logger->debug("Loaded Currency Precision from Cache :::" . json_encode($reachCache));
                $precision = $reachCache[$currencyCode];
            }
            else {
                $data = $this->getResource()->getPrecisionByCurrency($currencyCode);
                $this->_logger->debug("Currency info from DB :::" . json_encode($data));
                if (isset($data[$currencyCode])) {
                    $precision = $data[$currencyCode]['precision_unit'];
                    $this->_logger->debug("Getting precision from Database call :" . $precision);
                } else {
                    $this->_logger->debug("Getting precision from API call");
                    $url = $this->reachHelper->getApiUrl();
                    $this->_logger->debug(json_encode($url));
                    $url .= 'localize?MerchantId=' . $this->reachHelper->getMerchantId() . "&Currency=" . $currencyCode;
                    $this->_logger->debug("url to retreive currency precision: " . json_encode($url));
                    $rest = $this->httpRestFactory->create();
                    $rest->setUrl($url);
                    $response = $rest->executeGet();
                    $result = $response->getResponseData();
                    if (isset($result)) {
                        $precision = $result['Units'];
                        $this->getResource()->setPrecisionByCurrency($currencyCode, $precision);
                    }
                }
                $currencyVsPrecision = [$currencyCode => $precision];
                $this->cacher->saveDataInCache($currencyVsPrecision);
            }
            $precision_adjusted = ($precision > self::PRECISION_CUTOFF)? self::PRECISION_CUTOFF: $precision; //because of a bug in
            //our API (as per the note in description of JIRA MAG-100)
            $this->_logger->debug("in convert currency: precision adjusted :".$precision_adjusted);

        }
        return round($amount, $precision_adjusted); //when nothing coming back from api or db; verify with team
    }



}
