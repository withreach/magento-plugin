<?php

namespace Reach\Payment\Block\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;

class Savedcard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        \Reach\Payment\Model\Contract $openContract,
        array $data = []
    ) {
        $this->reachHelper = $reachHelper;
        $this->openContract = $openContract;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }
    
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getSavedCards()
    {
        $cards=[];
        $customerCards = $this->getCustomerCards();
        if ($customerCards && $customerCards->count()) {
            foreach ($customerCards as $card) {
                $cards[]=['type'=>$card->getMethod(),'number'=>$card->getIdentifier(),'contract_id'=>$card->getId()];
            }
        }
        return $cards;
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


    protected function getCustomerCards()
    {

        $customer = $this->getCustomer();
        if ($customer && $customer->getId()) {
            $collection = $this->openContract->getCollection();
            $collection->addFieldToFilter('customer_id', ['eq'=>$customer->getId()]);
            return $collection;
        }
        return null;
    }

    public function getRemoveUrl($cardId)
    {
        $query = ['card_id' => $cardId];
        return $this->getUrl('reach/customer/removecard', ['_secure' => $this->getRequest()->isSecure(),'_query' => $query]);
    }
}
