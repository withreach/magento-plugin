<?php

namespace Reach\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class CcConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = Cc::METHOD_CC;

    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var  \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var  \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var  \Reach\Payment\Model\Contract
     */
    protected $openContract;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $coreUrl;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \Magento\Framework\UrlInterface $coreUrl,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Contract $openContract,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->coreUrl = $coreUrl;
        $this->reachHelper = $reachHelper;
        $this->customerSession = $customerSession;
        $this->openContract = $openContract;
        $this->checkoutSession = $checkoutSession;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'reach_cc' => [
                    'oc_enabled'=>(boolean)$this->reachHelper->getAllowOpenContract(),
                    'open_contracts'=>$this->getOpenContracts()
                ],
            ],
        ] : [];
    }

    /**
     * Get Customer saved open contract
     *
     * @return array
     */
    protected function getOpenContracts()
    {
        $contracts = [];

        if (!$this->reachHelper->getAllowOpenContract()) {
            return $contracts;
        }

        if ($this->customerSession->isLoggedIn() && $this->customerSession->getCustomer()->getId()) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $collection = $this->openContract->getCollection();
            $collection->addFieldToFilter('customer_id', ['eq'=>$customerId]);
            foreach ($collection as $contract) {
                $contracts[] = ['contractId'=>$contract->getReachContractId(),'label'=>$contract->getMethod().' - '.$contract->getIdentifier()];
            }
        }
        return $contracts;
    }
}
