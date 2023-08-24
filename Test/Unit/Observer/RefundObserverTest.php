<?php

namespace Deuna\Now\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Deuna\Now\Observer\RefundObserver;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

class RefundObserverTest extends TestCase
{
    protected $objectManager;
    protected $eventObserver;
    protected $loggerMock;
    protected $logtailHandlerMock;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->loggerMock = $this->createMock(Logger::class);
        $this->logtailHandlerMock = $this->createMock(LogtailHandler::class);
    }

    public function testExecute()
    {
        $observer = $this->objectManager->getObject(Observer::class);

        $refundObserver = $this->objectManager->getObject(
            RefundObserver::class,
            [
                'logger' => $this->loggerMock,
                'logtailHandler' => $this->logtailHandlerMock,
            ]
        );

        $refundObserver->execute($observer);
    }
}
