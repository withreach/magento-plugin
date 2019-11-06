<?php

namespace Reach\Payment\Api;

/**
 * @api
 */
interface DutyCalculatorInterface
{
    /**
     * @param string $cartId
     * @param float $shippingCharge
     * @param string $shippingMethodCode
     * @param string $shippingCarrierCode
     * @param Magento\Quote\Api\Data\AddressInterface $address
     * @param boolean $apply
     * @return \Reach\Payment\Api\Data\DutyResponseInterface
     */
    public function getDutyandTax($cartId, $shippingCharge, $shippingMethodCode, $shippingCarrierCode, $address, $apply = false);
}
