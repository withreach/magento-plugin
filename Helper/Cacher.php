<?php

namespace Reach\Payment\Helper;

//use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

use Magento\Framework\Serialize\SerializerInterface;


/**
 * Helper Class
 */
//class Cacher extends AbstractHelper
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

    const REACH_CACHE_ID = 'reach_payment_custom_cache';
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
        $this->serializer   = $serializer;
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
