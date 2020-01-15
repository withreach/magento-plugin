<?php

namespace Reach\Payment\Model\Config\Source;

class TransportMode implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'AIR', 'label' => __('Air')],
            ['value' => 'OCEAN', 'label' => __('Ocean')],
            ['value' => 'GROUND', 'label' => __('Ground')]
        ];
    }
}
