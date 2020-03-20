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
        /*We are setting value to 'N/A' instead of '' or ' '
        that is because by the builtin method that saves data in the config table ; the '' is getting saved as null in
        the database. Now on retrieval that null causes problem. This is because if a config is null at a more specific
        scope like store then the logic looks for a value in the parent scope instead of stopping at that more specific
        scope/level.
        There are several ways to solve this. The shortest route that seemed to worked both in the database, dynamic
        scope control logic and integrating with DHL is this 'N/A' value.
        */
        return [
            ['value' => 'COURIER', 'label' => __('Courier')],
            ['value' => 'POST', 'label' => __('Post')],
            ['value' => 'N/A', 'label' => __('No Selection')]
        ];
    }
}
