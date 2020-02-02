<?php
namespace Reach\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CartChangedObserver implements ObserverInterface
{

    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_checkoutSession;


    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Checkout\Model\Session             $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session             $checkoutSession
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    //checkout_cart_save_after
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $this->_checkoutSession->setPrevCountry('');
            $this->_checkoutSession->setPrevRegion('');
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/oc-save-error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
        return $this;
    }


    /**
     * validate response
     *
     * @param array $response
     * @param string $nonce
     * @return boolean
     */
    /*
    protected function validateResponse($response, $nonce)
    {
        $nonce = str_replace(' ', '+', $nonce);
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $response, $key, true));
        return $signature == $nonce;
    }*/

}
