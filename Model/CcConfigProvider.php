<?php

namespace Reach\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;

class CcConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = Cc::METHOD_CC;
    protected $_storeManager;

    const superTypeCcPaymentMethod = 'Card';

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
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $_logger;


    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\Contract $openContract
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CcConfig $ccConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PaymentHelper $paymentHelper,
        \Magento\Framework\UrlInterface $coreUrl,
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Contract $openContract,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        Escaper $escaper,
        CcConfig $ccConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_storeManager = $storeManager;
        $this->escaper = $escaper;
        $this->coreUrl = $coreUrl;
        $this->reachHelper = $reachHelper;
        $this->customerSession = $customerSession;
        $this->openContract = $openContract;
        $this->checkoutSession = $checkoutSession;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->ccConfig = $ccConfig;
        $this->_logger = $logger;

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
                    'open_contracts'=>$this->getOpenContracts(),
                    'selected_currency'=>$this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
        //got idea from here: https://webkul.com/blog/adding-additional-variables-in-window-checkoutconfig-on-magento-2-checkout-page/
                    'availableTypes'=> $this->getCcAvailableTypes('Card'), //has something like
                    // ["AE"=>"American Express","VI"=>"Visa"]

                    'icons' => $this->getIcons()
                ]
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
            $collection->addFieldToFilter('closed_at', ['null' => true]);
            foreach ($collection as $contract) {
                $contracts[] =
                    [
                        'contractId'=>$contract->getReachContractId(),
                        'label'=>$contract->getMethod().' - '.$contract->getIdentifier(),
                        'contractCurrency'=>$contract->getCurrency()
                    ];
            }
        }
        return $contracts;
    }
    /** @var CcConfig
     */

    private $icons = [];

    /**
     * Get icons for available payment methods
     *
     * @return array
     */
    public function getIcons()
    {
        //https://magento.stackexchange.com/a/195972
        $types = $this->getCcAvailableTypes(self::superTypeCcPaymentMethod);//has something like
        // ["ae"=>"American Express","vi"=>"Visa"] in it;
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Magento_Payment::images/cc/' . strtolower($code) . '.png');
                list($width, $height) = getimagesize($asset->getSourceFile());
                $this->icons[$code] = [
                    'url' => $asset->getUrl(),
                    'width' => $width,
                    'height' => $height,
                    'title' => __($label),
                ];
            }
        }
        $this->_logger->debug('Icons:::'.json_encode($this->icons));
        return $this->icons;
    }


    public function getCcAvailableTypes($methodCode)
    {
        //Mapping from card ids in our system into shortened name that maps to card images that comes with magento_payment
        // module ; this is to be able to retrieve correct image asset info and reuse what comes with magento_payment
        // module
        $mapping=["AMEX"=>"AE", "VISA"=>"VI","DINERS"=>"DN","MC"=>"MC","DISC"=>"DI","JCB"=>"JCB","Diners"=>"DN",
            'MAESTRO'=>'MI', 'ELECTRON'=> 'EL'];

        $availableTypes=[];
        $methodCodeList = $this->reachHelper->getPaymentMethods()[$methodCode];

        foreach ($methodCodeList as $item) {
            $availableTypes[ $mapping[$item["Id"]]] = $item["Name"];
        }

        $this->_logger->debug("payment type stored into helper:::".json_encode(
                $methodCodeList));

        $this->_logger->debug('Payment types:::'.json_encode($availableTypes));
        return $availableTypes;
    }
}
