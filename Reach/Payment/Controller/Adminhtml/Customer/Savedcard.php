<?php

namespace Reach\Payment\Controller\Adminhtml\Customer;

class Savedcard extends \Magento\Customer\Controller\Adminhtml\Index
{
    protected $_publicActions = ['savedcard'];
    
    /**
     * Customer compare grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {

        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
