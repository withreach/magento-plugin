<?php

namespace Reach\Payment\Api;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use \DateTime;

/**
 * @api
 */
interface RestAccessTokenInterface
{
    /**
     * Retrieves an access token
     * 
     * @return string
     */
    public function getAccessToken();

    /**
     * Tests if have valid, active access token
     * 
     * @return bool
     */
    public function isTokenValid();

    /**
     * Get access token expiry date/time
     * 
     * @return DateTime
     */
    public function getTokenExpiry();
}
