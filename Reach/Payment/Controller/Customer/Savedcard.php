<?php

namespace Reach\Payment\Controller\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;

class Savedcard extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /*** @param \Magento\Framework\App\Action\Context $context*/
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->reachHelper = $reachHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * Saved Cards Index, shows a list of saved cards.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        try {
            $customer = $this->getCustomer();
            if ($customer && $customer->getId()) {
                $resultPage = $this->resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->prepend(__('Saved Cards - Reach Open Contracts'));
                return $resultPage;
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'customer/account',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
             return $this->resultRedirectFactory->create()->setPath(
                 'customer/account',
                 ['_secure' => $this->getRequest()->isSecure()]
             );
        }
    }

      /**
       * Return the Customer given the customer Id stored in the session.
       *
       * @return \Magento\Customer\Api\Data\CustomerInterface
       */
    protected function getCustomer()
    {
        return $this->customerRepository->getById($this->customerSession->getCustomerId());
    }
}
