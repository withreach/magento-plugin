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


    //Instead of using hardcoded values all over
    //the data values could be loaded from data providers
    //however during initial round not using data providers with so many parameters
    // makes development a bit easier

    //this runs to setup preconditions for every tests in this file
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
        $this->ccConfig = $this->getMockBuilder('\Magento\Payment\Model\CcConfig')
            ->disableOriginalConstructor()
            ->setMethods(['createAsset'])
            ->getMock();

        //https://www.magentoextensions.org/documentation/class_magento_1_1_framework_1_1_view_1_1_test_1_1_unit_1_1_asset_1_1_source_test.html
        //better to provide actual/official  github link to magento code base that has this unit test
        //$assetSource = $this->createMock('\Magento\Framework\View\Asset\Source');

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

    public function testGetIcons()
    {
        //https://magento.stackexchange.com/a/289287
        //https://www.magentoextensions.org/documentation/framework_2_view_2_asset_2_file_8php_source.html
        /*$fileObj = $this->getMockBuilder('Magento\Framework\View\Asset\File')
                        ->disableOriginalConstructor()
                        ->setMethods(['getFilePath', 'getContext']);
        */

        //https://www.magentoextensions.org/documentation/framework_2_view_2_test_2_unit_2_asset_2_source_test_8php_source.html
        //can we use use a mock object for the context instead? Even the test in magento that I looked at (where the class
        // under test was
        //dependent on context) I did not notice use of mocking for context (see we are not mocking this one). Copied
        // the style from there
        $context = new \Magento\Framework\View\Asset\File\FallbackContext(
            'http://example.com/static/',
            'frontend',
            'magento_theme',
            'en_US'
        );

        /*
        //this way could work too
        //https://www.magentoextensions.org/documentation/framework_2_view_2_test_2_unit_2_asset_2_source_test_8php_source.html
        $asset0 = $this->createMock(\Magento\Framework\View\Asset\File::class);
        $asset0->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $asset0->expects($this->any())
            ->method('getFilePath')
            ->willReturn('images/cc/ae.png');

        $asset0->expects($this->any())
            ->method('getUrl')
            ->willReturn('http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/ae.png');


        $asset0->expects($this->any())
            ->method('getPath')
            ->willReturn('images/cc/ae.png');
        $asset0->expects($this->any())
            ->method('getModule')
            ->willReturn('Magento_Payment');
        $asset0->expects($this->any())
            ->method('getContentType')
            ->willReturn('image/png');

        $asset1 = $this->createMock(\Magento\Framework\View\Asset\File::class);
        $asset1->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $asset1->expects($this->any())
            ->method('getFilePath')
            ->willReturn(['images/cc/vi.png']);
        $asset1->expects($this->any())
            ->method('getPath')
            ->willReturn('images/cc/vi.png');


        $asset1->expects($this->any())
            ->method('getPath')
            ->willReturn('images/cc/vi.png');


        $asset1->expects($this->any())
            ->method('getModule')
            ->willReturn('Magento_Payment');
        $asset1->expects($this->any())
            ->method('getContentType')
            ->willReturn('image/png');


        $asset1->expects($this->any())
            ->method('getUrl')
            ->willReturn('http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/vi.png');
        //using http instead of https because of `getimagesize(): Peer certificate CN= did not match expected CN=` error
        //the idea/hinst is in https://stackoverflow.com/a/8518616


        //https://github.com/magento/magento2/blob/2.2/lib/internal/Magento/Framework/View/Asset/Repository.php
        //https://www.lifewire.com/mime-types-by-content-type-3469108magento
        //https://magento.stackexchange.com/a/289287
        //https://stackoverflow.com/a/10964562


        //https://stackoverflow.com/a/10964562 very good when need to return two values during two calls ;
        //https://stackoverflow.com/a/43208045
        $asset0->method('getSourceFile')
            ->willReturn('http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/ae.png'

            );

        $asset1->method('getSourceFile')
            ->willReturn(
                'http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/vi.png'
            );
        //using http instead of https because of `getimagesize(): Peer certificate CN= did not match expected CN=` error
        //the idea/hinst is in https://stackoverflow.com/a/8518616

        $this->ccConfig->expects($this->at(0))->method('createAsset')->with('Magento_Payment::images/cc/ae.png')->willReturn(
            $asset0
        );
        $this->ccConfig->expects($this->at(1))->method('createAsset')->with('Magento_Payment::images/cc/vi.png')->willReturn(
            $asset1
        );
        */

        //this is alternative way of testing
        //https://stackoverflow.com/questions/43581318/how-to-send-an-array-of-test-cases-to-phpunit-willreturnonconsecutivecalls

        $asset = $this->createMock(\Magento\Framework\View\Asset\File::class);

        $asset->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $asset->expects($this->any())
            ->method('getFilePath')
            ->willReturnOnConsecutiveCalls('images/cc/ae.png',
                'images/cc/vi.png'
            );

        $asset->expects($this->any())
            ->method('getPath')
            ->willReturnOnConsecutiveCalls('images/cc/ae.png',
                'images/cc/vi.png'
            );

        $asset->expects($this->any())
            ->method('getUrl')
            ->willReturnOnConsecutiveCalls(
                'http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/ae.png',
                'http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/vi.png'
            );


        //how to return different values on different calls

        //the exactly is an assertion
        //could be $this->>any() which would not do any assertion on how many times it is executed.
        $asset->expects($this->exactly(2))
            ->method('getSourceFile')
            ->willReturnOnConsecutiveCalls(
                'http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/ae.png',
                'http://localhost/static/version1590112357/frontend/Magento/luma/en_US/Magento_Payment/images/cc/vi.png'
            );

        $asset->expects($this->any())
            ->method('getModule')
            ->willReturn('Magento_Payment');

        $asset->expects($this->any())
            ->method('getContentType')
            ->willReturn('image/png');

        //how to send different values for a parameter during different execution
        //the exactly is another assertion
        //could be $this->>any()
        $this->ccConfig->expects($this->exactly(2))->method('createAsset')->withConsecutive(
                ['Magento_Payment::images/cc/ae.png'],
                ['Magento_Payment::images/cc/vi.png']
            )
            ->willReturn(
                $asset
            );


        $this->assertEquals( "American Express",$this->ccConfigProvider->getIcons()['AE']['title'], json_encode($this->ccConfigProvider->getIcons()));
    }

    protected function tearDown()
    {
        unset($this->ccConfigProvider);
    }
}