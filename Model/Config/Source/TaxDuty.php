<?php

namespace Reach\Payment\Model\Config\Source;

class TaxDuty implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'disabled', 'label' => __('Do not show tax duty breakdown')],
            ['value' => 'shipping', 'label' => __('Show it on shipping step')],
            ['value' => 'summary', 'label' => __('Show it under order summary')],
            ['value' => 'both', 'label' => __('Display Tax & Duty on shipping step and order summary')],
        ];
    }
}
