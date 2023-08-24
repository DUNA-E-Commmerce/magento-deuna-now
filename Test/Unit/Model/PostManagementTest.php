<?php

namespace Deuna\Now\Test\Unit\Model;

use Deuna\Now\Model\PostManagement;
use PHPUnit\Framework\TestCase;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;

use Monolog\Logger;

use Deuna\Now\Helper\Data;
use Deuna\Now\Model\Order\ShippingMethods;
use Deuna\Now\Model\OrderTokens;

class PostManagementTest extends TestCase
{
    /**
     * @var PostManagement
     */
    private $postManagement;

    protected function setUp(): void
    {
        
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteManagementMock = $this->getMockBuilder(QuoteManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $quoteTokensmentMock = $this->getMockBuilder(OrderTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $criMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $deunaShippingMock = $this->getMockBuilder(ShippingMethods::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderManagementMock = $this->getMockBuilder(OrderManagementInterface::class)
            ->getMock();

        $this->postManagement = new PostManagement(
            $requestMock,
            $quoteManagementMock,
            $quoteTokensmentMock,
            $criMock,
            $helperMock,
            $customerFactoryMock,
            $customerRepositoryMock,
            $storeManagerMock,
            $orderRepositoryMock,
            $deunaShippingMock,
            $orderManagementMock
        );



    }

    public function testMapPaymentMethodAdyen()
    {
        $this->assertEquals('adyen_cc', $this->postManagement->mapPaymentMethod('adyen'));
    }

    public function testMapPaymentMethodEvopayment()
    {
        $this->assertEquals('tns_hpf', $this->postManagement->mapPaymentMethod('evopayment'));
    }

    public function testMapPaymentMethodAmex()
    {
        $this->assertEquals('amex_hpf', $this->postManagement->mapPaymentMethod('amex'));
    }

    public function testMapPaymentMethodEvopayment3ds()
    {
        $this->assertEquals('tns_hosted', $this->postManagement->mapPaymentMethod('evopayment_3ds'));
    }

    public function testMapPaymentMethodPaypalCommerce()
    {
        $this->assertEquals('paypal_express_tmp', $this->postManagement->mapPaymentMethod('paypal_commerce'));
    }

    public function testMapPaymentMethodUnknowMethod()
    {
        $this->assertEquals('deunacheckout', $this->postManagement->mapPaymentMethod('unknown_method'));
    }

    public function testMapPaymentMethodEmptyString()
    {
        $this->assertEquals('deunacheckout', $this->postManagement->mapPaymentMethod(''));
    }

    public function testMapPaymentMethodNull()
    {
        $this->assertEquals('deunacheckout', $this->postManagement->mapPaymentMethod(1));
    }

    public function testMapPaymentMethodTrue()
    {
        $this->assertEquals('deunacheckout', $this->postManagement->mapPaymentMethod(1));
    }

    public function testQuotePrepareEmptyValues()
    {
        $order = [];
        $email = '';
       
        $result = $this->postManagement->quotePrepare($order, $email);
        $this->assertNotEmpty($result);
    }

    public function testQuotePrepareNullValues()
    {
        $order = null;
        $email = null;
       
        $result = $this->postManagement->quotePrepare($order, $email);
        $this->assertNotEmpty($result);
    }

    public function testNotifyNullValues()
    {
        $result = $this->postManagement->notify();
        $this->assertNotEmpty($result);
    }

    public function testGetTokenNull()
    {
        $result = $this->postManagement->getToken();
        $this->assertNull($result);
    }

    public function testUpdatePaymentState()
    {
        $order = [];
        $payment_status = '';
        $totalAmount = '';

        $result = $this->postManagement->updatePaymentState($order, $payment_status, $totalAmount);
        $this->assertNull($result);
    }
    

}
