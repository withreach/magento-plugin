<?php

namespace Reach\Payment\Api;

/**
 * @api
 */
interface PayPalManagementInterface
{

     /**
     * @param string $cartId
     * @param string $deviceFingerprint
     * @return \Reach\Payment\Api\Data\ResponseInterface
     */
    public function savePaymentAndPlaceOrder($cartId,$deviceFingerprint);

    /**
     * @param string $cartId
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getQuoteById($cartId);
}
