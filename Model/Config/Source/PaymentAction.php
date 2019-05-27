<?php

namespace Reach\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package Reach\Payment\Model\Config\Source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ],
        ];
    }
}
