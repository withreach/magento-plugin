<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Reach\Payment\Block\Adminhtml\Form\Field;

/**
 * Custom import CSV file field for HS codes
 *
 */
class Import extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';


        $html .= parent::getElementHtml();

        return $html;
    }
}
