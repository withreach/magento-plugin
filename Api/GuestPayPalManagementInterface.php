<?php

namespace Reach\Payment\Api;

/**
 * @api
 */
interface GuestPayPalManagementInterface extends PayPalManagementInterface
{
    const GUEST_CHECKOOUT = true;
}
