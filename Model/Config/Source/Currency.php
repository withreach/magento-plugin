<?php

namespace Reach\Payment\Model\Config\Source;

class Currency implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'disabled', 'label' => __('Disabled')],
            ['value' => 'customer', 'label' => __('Reach determined by default, customer can change it')],
            ['value' => 'reach', 'label' => __('Reach determined, customer can not change')],
        ];
    }
}
