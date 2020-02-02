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
     * handles 'checkout_cart_save_before' core Magento event
     * basically cleans up the system state (session not database) necessary for triggering a new DHL api call when
     * 1. there is change in the cart and
     * 2. checkout is attempted.
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            //clearing previously saved country and state information in the checkout session
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
}
