<?php

namespace Reach\Payment\Model\Config\Source;

class EndUse implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'PERSONAL', 'label' => __('Personal')],
            ['value' => 'GIFTS', 'label' => __('Gifts')],
            ['value' => 'BUSINESS', 'label' => __('Business')]
        ];
    }
}
