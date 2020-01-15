<?php

namespace Reach\Payment\Model\Config\Source;

class ClearanceMode implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'COURIER', 'label' => __('Courier')],
            ['value' => 'POST', 'label' => __('Post')],
            ['value' => 'BLANK', 'label' => __('Blank')]
        ];
    }
}
