<?php
/**
 * Reason behind this Cacher class:
 * Most appropriate place to save precision values related to various currencies
 * is cache not session. This is because the precision of a currency does not change
 * from one customer to another. Wrote a custom Magento cache such that only that
 * cache can be cleared as needed instead of clearing all the caches.
 * This cached data is saved on Server side.
 */


namespace Reach\Payment\Helper;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

use Magento\Framework\Serialize\SerializerInterface;


/**
 * Helper Class

 * If this class is not a subclass of TagScope then we get error when we try to compile code for
 * dependency injection and interceptor generator as well as while running cache cleanup or 
 * flushing options.
 * This is the reason behind exetending TagScope instead of AbstractHelper class.
 */

class Cacher extends TagScope
{
    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    //Needed for saving and retrieving data.
    const REACH_CACHE_ID = 'reach_payment_custom_cache';

    //Is needed for clearing or flushing a specific cache instead of clearing everything
    // (or something with wider scope).
    const REACH_CACHE_TAG = 'REACH_PAYMENT_CUSTOM_CACHE';


    /**
     * @var
     */
     private $typeList;

    /**
     * @var \Magento\Framework\App\CacheInterface
    */
     private $cache;

    /**
     * @var array
     */
     private $data;

    /**
     * Constructor
     *
     * @param FrontendPool $cacheFrontendPool
     * @param \Psr\Log\LoggerInterface $logger
     * @param SerializerInterface $serialized
     * @param \Magento\Framework\App\CacheInterface $cache,
     * @param \Magento\Framework\App\Cache\TypeListInterface $typeList
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
        \Psr\Log\LoggerInterface $logger,
        SerializerInterface $serializer,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Cache\TypeListInterface $typeList
    ) {
        $this->_logger = $logger;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->typeList = $typeList;
        $this->data = [];
        parent::__construct($cacheFrontendPool->get(self::REACH_CACHE_ID),
            self::REACH_CACHE_TAG);
    }

    /**
     * @return array
     */
    public function loadDataFromCache(){
        $data = $this->cache->load(self::REACH_CACHE_ID);
        if (!$data) {
            return [];
        }

        $data = $this->serializer->unserialize($data);
        $this->data = $data;
        return $data;
    }

    /** array with key=>value pairs
     * @param array
     */
    public function saveDataInCache($newData){
        if (isset($newData)) {
            $expandedData = $this->data;
            foreach ($newData as $key => $value) {
                $expandedData[$key] = $value;
            }
            $this->data = $expandedData;
            $expandedData = $this->serializer->serialize($expandedData);
            $this->_logger->debug("Saving Currency Precision in Cache :::" . $expandedData);
            $this->cache->save($expandedData, self::REACH_CACHE_ID, array(self::REACH_CACHE_TAG));
        }
    }

    /*
     * To do; not required now
     * achieving equivalent effect with selectively clearing this cache with tag self::REACH_CACHE_TAG
     * public function invalidateCache() {

       $this->_typeList->invalidate(self::REACH_CACHE_ID);
    }*/

}
