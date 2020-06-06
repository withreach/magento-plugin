<?php


namespace Reach\Payment\Test\Unit\Model;


use Reach\Payment\Helper\Data;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Reach\Payment\Model\Contract;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\Escaper;
use \Magento\Payment\Helper\Data as PaymentHelper;
use \Magento\Payment\Model\CcConfig;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\UrlInterface;
use \Reach\Payment\Model\CcConfigProvider;

class CcConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentHelper MockObject
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $coreUrl;

    /**
     * @var Data MockObject
     */
    protected $reachHelper;

   /**
    * @var Contract MockObject
    */
    protected $openContract;

    /**
     * @var \Magento\Checkout\Model\Session MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session MockObject
     */
    protected $customerSession;

    /**
     * @var Escaper MockObject
     */
    protected $escaper;

    /**
     * @var CcConfig MockObject
     */
    protected $ccConfig;

    /**
     * @var LoggerInterface MockObject
     */
    protected $logger;
    /**
     * @var string[]
     */
    protected $methodCode = \Reach\Payment\Model\Cc::METHOD_CC;

    /**
     * @var CcConfigProvider
     */
    protected $ccConfigProvider;

    protected function setUp()
    {
        $this->reachHelper = $this->getMockBuilder('Reach\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->setMethods(['getReachEnabled', 'getCreditCardActive', 'isAvailable',
                'getSecret','getCheckOutUrl', 'getMerchantId',
                'getRefundUrl', 'getPaymentMethods','setPaymentMethods'])
            ->getMock();

        $this->reachHelper->method('getPaymentMethods')
            ->willReturn(["Card" => [
                                [
                                    "Id"=>"AMEX",
                                    "Name"=>"American Express"
                                ],
                                [
                                    "Id"=>"VISA",
                                    "Name"=>"Visa"
                                ]
                           ]
                       ]
            );

        $this->paymentHelper = $this->getMockBuilder('\Magento\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->setMethods(['getMethodInstance'])
            ->getMock();
        $methodInstance = $this->getMockBuilder('Reach\Payment\Model\Cc')
            ->disableOriginalConstructor()
            ->setMethods(['isAvailable'])
            ->getMock();
        $methodInstance->method('isAvailable')->willReturn(true);
        $this->paymentHelper->method('getMethodInstance')->with($this->methodCode)->willReturn($methodInstance);

        $this->createMock('\Magento\Payment\Helper\Data');
        $this->coreUrl = $this->createMock('\Magento\Framework\UrlInterface');
        $this->openContract =  $this->createMock('\Reach\Payment\Model\Contract');
        $this->checkoutSession = $this->createMock('Magento\Checkout\Model\Session');
        $this->customerSession = $this->createMock('Magento\Customer\Model\Session');
        $this->Escaper = $this->createMock('\Magento\Framework\Escaper');
        $this->ccConfig = $this->createMock('\Magento\Payment\Model\CcConfig');
        $this->logger = $this->createMock('\Psr\Log\LoggerInterface');

        $objectManager = new ObjectManager($this);

        $this->ccConfigProvider = $objectManager->getObject('Reach\Payment\Model\CcConfigProvider', [
                'coreUrl' => $this->coreUrl,
                'openContract' => $this->openContract,
                'checkoutSession' => $this->checkoutSession,
                'reachHelper' => $this->reachHelper,
                'customerSession' => $this->customerSession,
                'method' => $this->paymentHelper->getMethodInstance($this->methodCode),
                'ccConfig' => $this->ccConfig,
                '_logger' => $this->logger
            ]

        );

    }

    public function testGetCcAvailableTypes()
    {
        $this->assertEquals( ["AE"=>"American Express",
            "VI"=>"Visa"],$this->ccConfigProvider->getCcAvailableTypes('Card'), "Mapping Failed");
    }
}