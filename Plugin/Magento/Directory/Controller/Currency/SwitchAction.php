<?php


namespace Reach\Payment\Plugin\Magento\Directory\Controller\Currency;


class SwitchAction {
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * When currency is changing we are resetting part of session so that Duty and Tax are adjusted accordingly
     * with a new DHL call.
     * In case of before plugin return value is null or an array.
     * We could use after plugin instead. In that case the return value would be the usual or augmented
     * form of something called `result` (Magento convention)
     */
    public function beforeExecute( )
    {
        $this->_checkoutSession->setPrevCountry('');
        $this->_checkoutSession->setPrevRegion('');
        return;
    }
}