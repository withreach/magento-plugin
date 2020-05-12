<?php


namespace Reach\Payment\Test\Integration\EventObserver;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\TestFramework\ObjectManager;
use \Magento\Checkout\Model\Session;

/**
 *  Tests CartChangedObserver Class
 *
 * before cart changed event is dispatched; we would set previous country and
 * previous state to some values; after the event handler is executed previous
 * country and previous state would be set to blank for the assertions to pass
 */

class CartChangedObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    private $checkoutSession;

    private function dispatchEvent($event, array $eventData)
    {
        /** @var EventManager $eventManager */
        $eventManager = ObjectManager::getInstance()->create(EventManager::class);
        $eventManager->dispatch($event, $eventData);
    }

    public function setUp()
    {
        //Arrange
        $upOne = dirname(__DIR__, 1);
        $this->addressData = include $upOne . '/_files/address_data.php';

        $this->checkoutSession = ObjectManager::getInstance()->create(\Magento\Checkout\Model\Session::class);
        $this->checkoutSession->setPrevCountry($this->addressData['country_id']);
        $this->checkoutSession->setPrevRegion($this->addressData['region']);

    }

    public function testCartChangeClearingCountryState()
    {
        //Assert
        $this->assertEquals('US', $this->checkoutSession->getPrevCountry());
        $this->assertEquals($this->addressData['country_id'], $this->checkoutSession->getPrevCountry());

        //Act
        //what to dispatch came from etc/events.xml (from event name attribute)
        $this->dispatchEvent(
            "checkout_cart_save_after",
            []
        );

        //Assert
        $this->assertNotEquals($this->addressData['country_id'], $this->checkoutSession->getPrevCountry());
        $this->assertEquals('', $this->checkoutSession->getPrevCountry());
        $this->assertEquals('', $this->checkoutSession->getPrevRegion());
    }
}