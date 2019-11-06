<?php

namespace Reach\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Carriers implements ArrayInterface
{
    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    protected $storeManager;
  
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;
 
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
      
   /**
    * Constructor
    *
    * @param \Magento\Backend\Block\Template\Context $context
    * @param \Magento\Shipping\Model\Config $shippingConfig
    * @param array $data
    */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $data = []
    ) {
        $this->storeManager = $context->getStoreManager();
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig = $context->getScopeConfig();
    }
      
    /**
     * Get all shipping methods with allowed methods
     *
     * @return array
     */
    public function getAllCarriers()
    {
        $allCarriers = $this->shippingConfig->getAllCarriers($this->storeManager->getStore());
 
        $shippingMethodsArray = [];
        foreach ($allCarriers as $shippigCode => $shippingModel) {
            $allowedMethods = $shippingModel->getAllowedMethods();

            $shippingTitle = $this->scopeConfig->getValue('carriers/'.$shippigCode.'/title');
            
            $optionsGroup=[];
            if (count($allowedMethods)) {
                foreach ($allowedMethods as $k => $allowedMethod) {
                    $methodTitle = '';
                    if (!$allowedMethod) {
                        continue;
                    }

                    if (gettype($allowedMethod) == 'string') {
                        $methodTitle = $allowedMethod;
                    } else {
                        $methodTitle =$allowedMethod->getText();
                    }
                    $optionsGroup[] = [
                        'title' => $shippingTitle,
                        'label'=>$methodTitle,
                        'value' => $shippigCode.'_'.$k
                    ];
                }
            }

            if (count($optionsGroup)) {
                $shippingMethodsArray[]=[ 'label' => $shippingTitle,'value'=>$optionsGroup];
            } else {
                $shippingMethodsArray[] = [
                    'label' => $shippingTitle,
                    'value' => $shippigCode
                ];
            }
        }
        return $shippingMethodsArray;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getAllCarriers();
        return [
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ],
        ];
    }
}
