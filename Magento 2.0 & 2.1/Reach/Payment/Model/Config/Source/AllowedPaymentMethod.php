<?php

namespace Reach\Payment\Model\Config\Source;

class AllowedPaymentMethod implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'cc', 'label' => __('Credit Cards')],
            ['value' => 'paypal', 'label' => __('PayPal')],
            ['value' => 'ideal', 'label' => __('iDEAL')],
            ['value' => 'sadad', 'label' => __('SADAD')],
            ['value' => 'bank_transfer', 'label' => __('Bank Transfer')],
            ['value' => 'boleto', 'label' => __('Boleto')],
            ['value' => 'oxxo', 'label' => __('OXXO')],
        ];
    }
}
