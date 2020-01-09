<?php

namespace Reach\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;

/**
 * Class DutyConfigProvider
 * @package Reach\Payment\Model
 */
class DutyConfigProvider implements ConfigProviderInterface
{
    /**
     * @var FeeHelper
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @param FeeHelper $helper
     * @param Session $checkoutSession
     * @param CalculatorInterface $calculator
     */
    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $dutyConfig = [];
        $quote = $this->checkoutSession->getQuote();
        $dutyConfig['duty_title'] = 'Tax & Duties';
        //$extraFeeConfig['duty_amount'] = 50.00;
        $dutyConfig['show_hide_duty'] = true;
        $dutyConfig['testField'] = true;
        return $dutyConfig;
    }
}
