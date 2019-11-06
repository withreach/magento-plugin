<?php

namespace Reach\Payment\Model;

use \Reach\Payment\Api\GuestPayPalManagementInterface;

class GuestPayPalManagement extends PayPalManagement implements GuestPayPalManagementInterface
{
    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        $quoteIdMask = $this->getQuoteIdMaskFactory()->create()->load($cartId, 'masked_id');

        return $this->getQuoteRepository()->get($quoteIdMask->getQuoteId());
    }
}
