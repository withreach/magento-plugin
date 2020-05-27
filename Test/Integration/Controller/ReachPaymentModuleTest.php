<?php


namespace Reach\Payment\Test\Integration\Controller;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\TestFramework\ObjectManager;

class ReachPaymentModuleTest extends \PHPUnit\Framework\TestCase
{
    private $moduleName = 'Reach_Payment';
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function testModuleExists()
    {
        //$this->expectOutputString('');
        //$this->fail("attempt to live and breathe M2 ITF");
        $registrar = new ComponentRegistrar();
        $paths = $registrar->getPaths(ComponentRegistrar::MODULE);
        var_dump($paths[$this->moduleName]);
        $this->assertArrayHasKey($this->moduleName, $paths);
        //$this->assertArrayHasKey('Reach_Payment', $paths);
    }


    public function testTheModuleIsKnownAndEnabledTest()
    {
        //$this->objectManager = ObjectManager::getInstance();
        /** @var ModuleList $moduleList */
        $moduleList = $this->objectManager->create(ModuleList::class);
        $message = sprintf('The module %s is not enabled', $this->moduleName);
        $this->assertTrue(
            $moduleList->has($this->moduleName), $message
        );
    }
    
}