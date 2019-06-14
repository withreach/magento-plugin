<?php

namespace Reach\Payment\Controller\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;

class Removecard extends \Magento\Framework\App\Action\Action
{

    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /*** @param \Magento\Framework\App\Action\Context $context      */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Reach\Payment\Helper\Data $reachHelper,
        CustomerRepositoryInterface $customerRepository,
        \Reach\Payment\Model\Contract $openContract,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->reachHelper = $reachHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->openContract = $openContract;
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
                $cardId = $this->getRequest()->getParam('card_id');
                $this->openContract->load($cardId);
                if ($this->openContract->getId()) {
                    $this->openContract->delete();
                    $this->messageManager->addSuccess(__("Your card has been removed"));
                    return $this->resultRedirectFactory->create()->setPath(
                        'reach/customer/savedcard/',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                } else {
                    $this->messageManager->addError(__("Card does not exist"));
                    return $this->resultRedirectFactory->create()->setPath(
                        'reach/customer/savedcard/',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'customer/account',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath(
                'reach/customer/savedcard/',
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
