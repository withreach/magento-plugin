<?php


namespace Reach\Payment\Plugin\Magento\Checkout\Block\Checkout;


class LayoutProcessor
{
    /**
     * Process js Layout of block.
     * UI item is getting removed
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param \Closure $proceed
     * @param array $jsLayout
     * @return array
     */
    public function aroundProcess($subject, $proceed, $jsLayout)
    {
        unset($jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['grand-total']['config']['basicCurrencyMessage']);
        $returnValue = $proceed($jsLayout);
        return $returnValue;
    }
}