<?php

namespace Reach\Payment\Model\Config\Source;

class PricingStrategy implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'MAXIMUM', 'label' => __('MAXIMUM')],
            ['value' => 'MINIMUM', 'label' => __('MINIMUM')],
            ['value' => 'AVERAGE', 'label' => __('AVERAGE')],
            ['value' => 'EXACT',   'label' => __('EXACT')],
        ];
    }
}
