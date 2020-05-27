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

class CcTest extends TestCase
{
    /**
     * @var Reach\Payment\Model\Cc
     */
    private $cc;
    /**
     * @var Reach MockObject
     */
    private $reach;

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
     * @var InfoInterface MockObject
     */
    protected $paymentObj;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->_coresession = $this->createMock('Magento\Framework\Session\SessionManagerInterface');
        $this->checkoutSession = $this->createMock('Magento\Checkout\Model\Session');
        $this->httpRestFactory = $this->createMock('Reach\Payment\Model\Api\HttpRestFactory');
        $this->reachCurrency = $this->createMock('Reach\Payment\Model\Currency');
        $this->reachHelper = $this->createMock('Reach\Payment\Helper\Data');
        $this->reachPayment = $this->createMock('Reach\Payment\Model\Reach');
        $this->store = $this->createMock('Magento\Store\Model\Store');
        $this->storeManager = $this->createMock('Magento\Store\Model\StoreManager');
        $this->paymentObj = $this->createMock('Magento\Payment\Model\InfoInterface');
        //$this->paymentObj->method()
        $this->cc = $objectManager->getObject('Reach\Payment\Model\Cc', [
                'coreSession' => $this->_coresession,
                'storeManager' => $this->storeManager,
                'reachHelper' => $this->reachHelper,
                'reachCurrency' => $this->reachCurrency,
                'reachPayment' => $this->reachPayment,
            ]
        );

    }

    /**
     * @param $expectedOutcome
     * @param $isReachEnabled
     * @param $isReachCcEnabled
     * @param $storeId
     * @param $paymentMethod
     * @param $isPaymentMethodAvailable
     * @dataProvider ccDataProvider
     */
    public function testCcIsAvailable($expectedOutcome, $isReachEnabled, $isReachCcEnabled, $storeId, $paymentMethod, $isPaymentMethodAvailable)
    {


        $this->store->method('getId')->willReturn($storeId);
        $this->storeManager->method('getStore')->willReturn($this->store );


        $this->reachHelper->expects($this->once())->method('getReachEnabled')->willReturn($isReachEnabled);
        $this->reachHelper->expects($this->any())->method('getCreditCardActive')->with(self::PATH, $this->storeManager->getStore()->getId())->willReturn($isReachCcEnabled);
        $this->reachPayment->expects($this->any())->method('isAvailable')->with($paymentMethod)->willReturn($isPaymentMethodAvailable);
        $this->assertEquals($expectedOutcome, $this->cc->isAvailable());
    }


    public function ccDataProvider()
    {
        return [
              'Cc Enabled Case 1' => [ true, true, true, 1, 'reach_cc', true],
              'Cc Disabled Case 1' => [ false, false, true, 1, 'reach_cc', true],
              'Cc Disabled Case 2' => [ false, false, false, 1, 'reach_cc', true],
              'Cc Disabled Case 3' => [ false, true, false, 1, 'reach_cc', true],
              'Cc Disabled Case 4' => [ false, true, true, 1, 'reach_cc', false]
        ];
    }

}