<?php


namespace Reach\Payment\Test\Unit\Model;


use Magento\Payment\Model\Method\ConfigInterfaceFactory;
use Reach\Payment\Model\Reach;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use Magento\Framework\Session\SessionManagerInterface;
use Reach\Payment\Model\Currency;
use Reach\Payment\Helper\Data;
use Magento\Checkout\Model\Session;
use Reach\Payment\Model\Api\HttpRestFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Payment\Model\InfoInterface;

class CcCodeRefactoredTest extends TestCase
{
    /**
     * @var Reach\Payment\Model\Cc
     */
    private $cc;
    /**
     * @var Reach MockObject
     */
    private $reachPayment;

    /**
     * @var  SessionManagerInterface MockObject
     */

    protected $_coresession;

    /**
     * @var  Currency MockObject
     */
    protected $reachCurrency;

    /**
     * @var Data MockObject
     */
    protected $reachHelper;

    /**
     * @var Session MockObject
     */
    protected $checkoutSession;

    /**
     * @var HttpRestFactory MockObject
     */
    protected $httpRestFactory;

    /**
     * @var HttpTextFactory MockObject
     */
    protected $HttpTextFactory;

    /**
     * @var bool
     */

    private $example = true;

    /**
     * @var string
     */


    const PATH = 'payment/reach_cc/active';


    /**
     * @var ScopeConfigInterface MockObject
     */
    protected $scopeConfig;

    /**
     *  @var StoreManager MockObject
     */
    protected $storeManager;

    /**
     * @var Store MockObject
     */
      protected $store;
    /**
     * @var UrlInterface MockObject
     */

    protected $coreUrl;

    /**
    @var \Magento\Payment\Model\Method\Cc   MockObject //AbstractMethod
     */
    protected $ccMethodMock;

    /**
     * @var  \Magento\Sales\Model\Order\Payment  MockObject    //Magento\Payment\Model\InfoInterface
     */
    protected $payment;

    /**
     * @var \Magento\Sales\Model\Order MockObject

     */
    protected $order;


    /**
     * @var \Reach\Payment\Api\Data\HttpResponseInterface MockObject
     *
     */
    protected $httpResponseMock;

    /**
     * @var \Reach\Payment\Model\Api\Http  MockObject
     *
     */
    protected $http;

    /**
     * @var \Reach\Payment\Model\Api\HttpText MockObject
     */
    protected $rest;

    /**
     * @var \Reach\Payment\Helper\CcHelper MockObject
     */
    protected $ccHelper;

    /**
     * @var Magento\Payment\Model\InfoInterface MockObject
     */


    protected $info;


    /**
     * @var request array
     */
    protected $request;


    protected function setUp()
    {
        //place holder for now -> can be moved to dataprovider
        //not getting used at this point but will be used shortly
        $this->request = [
            'MerchantId' => '12345',
            'ReferenceId' => 1,
            'Consumer' => null,
            'Notify' => 'some url',
            'ConsumerCurrency' =>'CAD',
            'RateOfferId' => 3,
            'DeviceFingerprint' => 'xyzwert',
            'ContractId' =>'contract 1',
            'StashId' =>'xyz',

            'PaymentMethod' => 'reach_cc',
            'OpenContract' => true,
            'Items' =>[
                [
                    'Sku' => 'S12345',
                    'ConsumerPrice'=>34.0,
                    'Quantity' => 2
                ]
            ],
            'ShippingRequired'=> true,
            'Shipping' => [
                'ConsumerDuty' => 7.02,
                'ConsumerPrice' => 34.0,
                'ConsumerTaxes' =>3.0,

            ] ,
            'ConsumerTotal' => 44.02,
            'Capture' => false
        ];

        $objectManager = new ObjectManager($this);


        $this->httpRestFactory = $this->getMockBuilder('Reach\Payment\Model\Api\HttpRestFactory')
            ->disableOriginalConstructor()->setMethods(['create'])
            ->getMock();

        $this->httpResponseMock = $this->createMock('Reach\Payment\Api\Data\HttpResponseInterface');



        $this->ccHelper = $this->getMockBuilder('Reach\Payment\Helper\CcHelper')
            ->disableOriginalConstructor()
            ->setMethods(['_buildCheckoutRequest', 'getConsumerInfo', 'getCallbackUrl', 'processErrors', 'setTransStatus','validateResponse'])
            ->getMock();  //so far using only '_buildCheckoutRequest' and 'validateResponse'


        //is not used at this moment but would be needed in near future
        /*
        $this->_coresession = $this->getMockBuilder('Magento\Framework\Session\SessionManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $body = [];  //place holder; different values can be loaded using dataprovider
        $this->http->method('executePost')->with($body)->willReturn($this->httpResponseMock );
        $this->store = $this->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManager')
            ->disableOriginalConstructor()
            ->getMock();
        */
        $this->http = $this->getMockBuilder('Reach\Payment\Model\Api\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpText = $this->getMockBuilder('Reach\Payment\Model\Api\HttpText')
            ->disableOriginalConstructor()
            ->setMethods(['processResponse','setContentType', 'executePost'])  //at present using only 'executePost' during mocking
            ->getMock();


        $this->httpTextFactory = $this->getMockBuilder('Reach\Payment\Model\Api\HttpTextFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
          ->getMock();


        $this->reachCurrency = $this->getMockBuilder('Reach\Payment\Model\Currency')
            ->disableOriginalConstructor()
            ->getMock();

        $this->reachHelper = $this->getMockBuilder('Reach\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->setMethods(['getReachEnabled', 'getCreditCardActive', 'isAvailable', 'getSecret','getCheckOutUrl', 'getMerchantId',
                'getRefundUrl'])
            ->getMock();




        $this->reachPayment = $this->getMockBuilder('Reach\Payment\Model\Reach')
            ->disableOriginalConstructor()
            ->getMock();


        $this->order =  $this->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getInfoInstance', 'setAdditionalInformation',
                'getAdditionalInformation', 'getMethodInstance','setInfoInstance', 'getData'])
            ->getMock();

        //$this->payment->method('getOrder')->willReturn($this->order);
        //$this->payment->expects($this->any())->method('setInfoInstance')->with($this->payment);
        //$this->payment->expects($this->any())->method('getInfoInstance')->willReturn($this->payment);

        $this->ccMethodMock = $this->getMockBuilder('Magento\Payment\Model\Method\Cc')
            ->disableOriginalConstructor()
            ->setMethods([
                'getInfoInstance',
                'getAdditionalInformation',
                'validateResponse',
                'processErrors',
                'setTransStatus',
                'getData',

            ])
            ->getMock();

        $this->info = $this->getMockBuilder('Magento\Payment\Model\InfoInterface')
            ->disableOriginalConstructor()
            ->setMethods(['setAdditionalInformation', 'getAdditionalInformation', 'hasAdditionalInformation', 'encrypt', 'decrypt',
            'unsAdditionalInformation', 'getMethodInstance', 'getParentTransactionId'])
            ->getMock(); //even though not suing all of these methods ... without those the code would error out saying
        //Class Mock_InfoInterface_xyz contains 5 abstract methods and must therefore be declared abstract or implement the remaining methods





        $amount= 300.50;


        $this->cc = $objectManager->getObject('Reach\Payment\Model\Cc', [
                //'coreSession' => $this->_coresession,
                //'storeManager' => $this->storeManager,
                'reachHelper' => $this->reachHelper,
                'reachCurrency' => $this->reachCurrency,
                'reachPayment' => $this->reachPayment,
                'ccHelper' => $this->ccHelper,
                'ccMethodMock' =>$this->ccMethodMock,
                'payment' => $this->payment,
                'info' =>$this->info,
                'httpTextFactory' => $this->httpTextFactory,
            ]
        );


    }

    protected function tearDown()
    {
        unset($this->cc);
    }



    //https://www.url-encode-decode.com/
    //https://www.php.net/manual/en/function.parse-str.php
    public function testAuthorize()
    {
        $amount = 300.50;

        //response=Suraiya's Amazing Test
        //signature=abcd
        //url encoded Suraiya's Amazing Test  and abcd separately using https://www.php.net/manual/en/function.parse-str.php
        //and then combined with an &
        //how to form the input came from looking at the way used parse_str (https://www.php.net/manual/en/function.parse-str.php)
        //was used in callCurl method in Model/Cc.php that is parse_str($responseString, $response);
        $this->httpResponseMock->method('setResponseData')->with('response=Suraiya%27s+Amazing+Test&signature=abcd');
        $this->httpResponseMock->method('getResponseData')->willReturn('response=Suraiya%27s+Amazing+Test&signature=abcd');


        $this->httpText->method('processResponse')->willReturn($this->httpResponseMock);
        $this->httpText->method('executePost')->willReturn($this->httpResponseMock );
        $this->rest = $this->httpTextFactory->method('create')->willReturn($this->httpText);

        //not used at this phase
        //$this->reachHelper->method('getSecret')->willReturn("lAjmbItVB0zGiK8o0DLX5yw1W1l6J325Dlro4lKFmKWUefJeteBzc8199mFSpIX3");

        $merchantId = '12345'; //move that to data provider at some point
        $this->reachHelper->method('getMerchantId')->willReturn($merchantId);

        $refundUrl = 'placeholder refund url';
        $this->reachHelper->method('getRefundUrl')->willReturn($refundUrl);
        //all the hardcoded params within () for with and willReturn can be loaded from a data provider
        $this->info->expects($this->any())->method('getAdditionalInformation')->with("stash_id")->willReturn('abcdefg');
        $this->info->expects($this->any())->method('getAdditionalInformation')->with('contract_id')->willReturn('abcdefg');
        $this->info->expects($this->any())->method('getAdditionalInformation')->with('oc_selected')->willReturn(false);
        $this->info->expects($this->any())->method('getParentTransactionId')->willReturn('abcdefgfgdfhghgfhfghfhgfhgfhfghfghgfhfghfhfhfghf-capture');

        $this->ccMethodMock->method('getInfoInstance')->willReturn($this->info );
        $this->ccMethodMock->method('getData')->with('info_instance')->willReturn($this->info );
        $this->ccHelper->method('_buildCheckoutRequest')
            ->with($this->payment, $amount,  $this->reachHelper,
                $this->cc , $this->reachCurrency)->willReturn($this->request)  ;

        $this->ccHelper->method('validateResponse')->with('Suraiya\'s Amazing Test','abcd')->willReturn(true);


        $this->assertEquals( $this->cc, $this->cc->authorize($this->payment, $amount));
    }


    public function testRefund()
    {
        $this->ccHelper->method('getReferenceIdForRefund')->willReturn($this->payment);
    }
}