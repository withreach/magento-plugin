<?php

namespace Reach\Payment\Model\Config\Source;

class Pricing implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'MAXIMUM', 'label' => __('Maximum')],
            ['value' => 'MINIMUM', 'label' => __('Minimum')],
            ['value' => 'EXACT', 'label' => __('Exact')],
        ];
    }
}
