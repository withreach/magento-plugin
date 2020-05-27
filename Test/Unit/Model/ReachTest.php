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
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class ReachTest extends TestCase
{
    /**
     * @var Reach\Payment\Model\Cc
     */
    private $cc;
    /**
     * @var Reach\Payment\Model\Reach
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


    private $methodName = 'Card';


/**
 * @var ScopeConfigInterface MockObject
 */
protected $scopeConfig;

/**
 *  @var StoreManagerInterface MockObject
 */
protected $storeManager;

/**
 * @var UrlInterface MockObject
 */

protected $coreUrl;


    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->_coresession = $this->createMock('Magento\Framework\Session\SessionManagerInterface');
        $this->checkoutSession = $this->createMock('Magento\Checkout\Model\Session');
        $this->httpRestFactory = $this->createMock('Reach\Payment\Model\Api\HttpRestFactory');
        $this->reachCurrency = $this->createMock('Reach\Payment\Model\Currency');
        $this->reachHelper = $this->createMock('Reach\Payment\Helper\Data');
        $this->reachPayment = $this->createMock(Reach::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        /*$this->reach = $objectManager->getObject(Reach::class,
            [ 'coreSession' => $this->_coresession,
              'reachCurrency' => $this->reachCurrency,
              'reachHelper' => $this->reachHelper,
              'checkoutSession' => $this->checkoutSession,
              'httpRestFactory' => $this->httpRestFactory
            ]);*/
        $this->cc = $objectManager->getObject(Cc::class,
        [
        'storeManager' => $this->storeManager,
        'reachHelper' => $this->reachHelper,
        'coreUrl' => $this->coreUrl,
        'reachCurrency' => $this->reachCurrency,
        'reachPayment' => $this->reachPayment,
        'httpTextFactory' => $this->httpTextFactory
            ]
        );

    }

    /**
     * @param $localizeCurrency
     * @param $currencyArray1
     * @param $currencyArray2
     * @dataProvider expandedLocalizeCurrencyProvider
     */
    public function testGetLocalize($localizeCurrency, $currencyArray1, $currencyArray2)
    {
        $this->_coresession->expects($this->once())->method('getLocalize')->willReturn($currencyArray1);
        $this->reachCurrency->expects($this->once())->method('getLocalizeCurrency')->willReturn($currencyArray2);
        $this->assertEquals($localizeCurrency, $this->reach->getLocalize()['currency']);
    }

    public function expandedLocalizeCurrencyProvider()
    {
        return [

            "About CAD" => ['CAD',
                  null,
                 [
                    'currency'=>'CAD',
                    'symbol'=>'$',
                    'country'=>'CA'
                ]
            ],
            "About USD" => ['USD',
                 [
                    'currency'=>'USD',
                    'symbol'=>'$',
                    'country'=>'US'
                 ],
                 [
                    'currency'=>'CAD',
                    'symbol'=>'$',
                    'country'=>'CA'
                ]
            ]

        ];
    }


    /**
     * @param $currency
     * @param $currencyArray
     * @dataProvider localizeCurrencyProvider
     */
    public function testLocalizeCurrency($currency, $currencyArray)
    {
        $this->reachCurrency->expects($this->once())->method('getLocalizeCurrency')->willReturn($currencyArray);
        $this->assertEquals($currency, $this->reachCurrency->getLocalizeCurrency()['currency']);
    }

    public function localizeCurrencyProvider()
    {
        return [

             "About CAD" => ['CAD',
                     [
                        'currency'=>'CAD',
                        'symbol'=>'$',
                        'country'=>'CA'
                    ]
             ],
            "About USD" => ['USD',
                  [
                    'currency'=>'USD',
                    'symbol'=>'$',
                    'country'=>'US'
                 ]
            ]

        ];
    }


    /**
     * @param $isTrue
     * @param $methodName
     * @dataProvider reachEnabledProvider
     */
    public function testReachEnabled($isTrue, $path)
    {
        $this->reachHelper->expects($this->once())->method('getConfigValue')->with($path)->willReturn($this->example);
        $this->assertEquals($isTrue, $this->reachHelper->getConfigValue($path));

    }

    const CONFIG_REACH_ENABLED = 'reach/global/active';

    public function reachEnabledProvider()
    {
        return [
            "REACH Enabled" => [true, self::CONFIG_REACH_ENABLED]
        ];
    }
}