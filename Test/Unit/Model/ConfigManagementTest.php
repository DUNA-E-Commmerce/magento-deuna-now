<?php

namespace Deuna\Now\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Deuna\Now\Model\ConfigManagement;

class ConfigManagementTest extends TestCase
{
    protected $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
    }

    /**
     * Prueba el método getPublicKey() en un entorno de producción.
     * Debe simular la configuración de un entorno de producción y devolver la clave pública correspondiente.
     */
    public function testGetPublicKeyProductionEnvironment()
    {
        $this->scopeConfigMock->method('getValue')
            ->withConsecutive(
                [$this->equalTo('payment/deuna/environment')],
                [$this->equalTo('payment/deuna/public_key_prod')]
            )
            ->willReturnOnConsecutiveCalls(
                'production',
                'production_public_key'
            );

        $configManagement = new ConfigManagement($this->scopeConfigMock);

        $result = $configManagement->getPublicKey();

        $this->assertEquals('production_public_key', $result);
    }


    /**
     * Prueba el método getPublicKey() en un entorno de sandbox.
     * Debe simular la configuración de un entorno de sandbox y devolver la clave pública correspondiente.
     */
    public function testGetPublicKeySandboxEnvironment()
    {
        $this->scopeConfigMock->method('getValue')
            ->withConsecutive(
                [$this->equalTo('payment/deuna/environment')],
                [$this->equalTo('payment/deuna/public_key_sandbox')]
            )
            ->willReturnOnConsecutiveCalls(
                'sandbox',
                'sandbox_public_key'
            );

        $configManagement = new ConfigManagement($this->scopeConfigMock);

        $result = $configManagement->getPublicKey();
        $this->assertEquals('sandbox_public_key', $result);
    }

    /**
     * Prueba el método getPublicKey() en un entorno de null.
     * Debe simular la configuración de un entorno de null y devolver la clave pública correspondiente.
     */
    public function testGetPublicKeyNullEnvironment()
    {
        $this->scopeConfigMock->method('getValue')
            ->withConsecutive(
                [$this->equalTo('payment/deuna/environment')],
                [$this->equalTo('payment/deuna/public_key_sandbox')]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                'sandbox_public_key'
            );

        $configManagement = new ConfigManagement($this->scopeConfigMock);

        $result = $configManagement->getPublicKey();
        $this->assertEquals('sandbox_public_key', $result);
    }

    /**
     * Prueba el método getPublicKey() en un entorno de null.
     * Debe simular la configuración de un entorno de null y devolver la clave pública correspondiente.
     */
    public function testGetPublicKeyEmptyStringEnvironment()
    {
        $this->scopeConfigMock->method('getValue')
            ->withConsecutive(
                [$this->equalTo('payment/deuna/environment')],
                [$this->equalTo('payment/deuna/public_key_sandbox')]
            )
            ->willReturnOnConsecutiveCalls(
                '',
                'sandbox_public_key'
            );

        $configManagement = new ConfigManagement($this->scopeConfigMock);

        $result = $configManagement->getPublicKey();
        $this->assertEquals('sandbox_public_key', $result);
    }
}
