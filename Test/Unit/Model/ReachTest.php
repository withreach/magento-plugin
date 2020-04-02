<?php


namespace Reach\Payment\Test\Unit\Model;


use Reach\Payment\Model\Reach;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use Magento\Framework\Session\SessionManagerInterface;
use Reach\Payment\Model\Currency;
use Reach\Payment\Helper\Data;
use Magento\Checkout\Model\Session;
use Reach\Payment\Model\Api\HttpRestFactory;

class ReachTest extends TestCase
{
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
    protected $currencyModel;

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
     * @var Data MockObject
     */

    protected $helper;


    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->_coresession = $this->createMock('Magento\Framework\Session\SessionManagerInterface');
        $this->checkoutSession = $this->createMock('Magento\Checkout\Model\Session', [], [], '', false);
        $this->httpRestFactory = $this->createMock('Reach\Payment\Model\Api\HttpRestFactory', $objectManager);
        $this->currencyModel = $this->createMock('Reach\Payment\Model\Currency');
        $this->reachHelper = $this->createMock('Reach\Payment\Helper\Data');
        $this->reach = $objectManager->getObject("Reach\Payment\Model\Reach",
            [ 'coreSession' => $this->_coresession,
              'currencyModel' => $this->currencyModel,
              'reachHelper' => $this->reachHelper,
              'checkoutSession' => $this->checkoutSession,
              'httpRestFactory' => $this->httpRestFactory
            ]);

    }



    /**
     * @param $isTrue
     * @param $methodName
     * @dataProvider availabilityProvider
     */
    public function testIsAvailable($isTrue, $methodName)
    {
        $this->assertEquals($isTrue, $this->reach->isAvailable($methodName));
    }

    public function availabilityProvider()
    {
        return [
             "paypal available" => [true, 'Online'],
             "cc available" => [true, 'Card'],
             "invalid method" => [false, '1-800-junk']

        ];
    }


    /**
     * @param $isTrue
     * @param $methodName
     * @dataProvider reachEnabledProvider
     */
    public function testReachEnabled($isTrue, $path)
    {
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