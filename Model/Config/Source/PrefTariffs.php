<?php

namespace Reach\Payment\Model\Config\Source;

class PrefTariffs implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'true', 'label' => __('True')],
            ['value' => 'false', 'label' => __('False')]
        ];
    }
}
