<?php
namespace Reach\Payment\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

class SavedCard extends \Magento\Framework\View\Element\Template implements TabInterface
{

    protected $_systemStore;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }
    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Saved Cards');
    }
    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Saved Cards');
    }
    /**
     * @return bool
     */
    public function canShowTab()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        /*if ($this->getCustomerId()) {
            return true;
        }
        return false;*/
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }
    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }
    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        //replace the tab with the url you want
        return $this->getUrl('reach/customer/savedcard', ['_current' => true]);
        //return '';
    }
    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return true;
    }
}
