<?php

namespace Deuna\Now\Test\Unit\Observer;


use PHPUnit\Framework\TestCase;
use Deuna\Now\Observer\OrderUpdateObserver;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;
use Deuna\Now\Helper\RequestHelper;

class OrderUpdateObserverTest extends TestCase
{
    public function testCancelOrderAuthorized()
    {
        $mockedLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedRequestHelper = $this->getMockBuilder(RequestHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observer = new OrderUpdateObserver($mockedLogger, $mockedRequestHelper);

        $orderToken = 'your_order_token';
        $orderDeunaStatus = 'authorized';
        $observer->cancelOrder($orderToken, $orderDeunaStatus);

    }

}
