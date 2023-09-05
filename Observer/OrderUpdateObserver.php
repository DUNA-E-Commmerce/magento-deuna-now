<?php

namespace Deuna\Now\Observer;

use Magento\Framework\Event\ObserverInterface;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

class OrderUpdateObserver implements ObserverInterface
{
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';

    protected $logger;

    public function __construct() {
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $state = $order->getState();
        $status = $order->getStatus();

        $this->logger->debug('Current State: ' . $state . ' | Status: ' . $status, [
            'orderId' => $order->getId(),
        ]);

        if (in_array($state, ['canceled', 'closed']) || in_array($status, ['canceled', 'closed'])){
            $orderId = $order->getId();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $orderRepository = $objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);

            $orderId = $order->getId();

            $order = $orderRepository->get($orderId);
            $payment = $order->getPayment();
            $orderToken = $payment->getAdditionalInformation('token');
            $orderDeunaStatus = $payment->getAdditionalInformation('deuna_payment_status');

            try {
                $resp = $this->cancelOrder($orderToken, $orderDeunaStatus);

                $this->logger->debug("Order {$orderId} has been canceled successfully", [
                    'orderId' => $orderId,
                    'orderToken' => $orderToken,
                    'response' => $resp,
                ]);
            } catch (\Exception $e) {
                $this->logger->critical("Error canceling order ID: {$orderId}", [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTrace(),
                ]);
            }
        }
    }

    private function cancelOrder($orderToken, $orderDeunaStatus)
    {
        $endpoint = "/merchants/orders/{$orderToken}/cancel";

        if ($orderDeunaStatus === 'authorized'){
            $endpoint = "/merchants/orders/{$orderToken}/void";
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $requestHelper = $objectManager->get(\Deuna\Now\Helper\RequestHelper::class);
        $requestHelper->request($endpoint, 'POST');
    }

}

