<?php

namespace Reach\Payment\Model;

use \Reach\Payment\Api\GuestDutyCalculatorInterface;

class GuestDutyCalculator extends DutyCalculator implements GuestDutyCalculatorInterface
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
