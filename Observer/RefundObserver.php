<?php

namespace Deuna\Now\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

class RefundObserver implements ObserverInterface
{
    /**
     *  @var \Psr\Log\LoggerInterface $logger
     */
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';

    protected $logger;

    public function __construct() {
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $orderId = $creditmemo->getOrderId();
        $payment = $order->getPayment();
        $orderToken = $payment->getAdditionalInformation('token');
        
        $reason = $creditmemo->getCustomerNote();

        $creditmemoId = $creditmemo->getId();

        $creditmemoData = $creditmemo->getData();

        $totalRefunded = $creditmemoData["base_grand_total"];

        $this->logger->debug("Order {$orderId} in process Refund ...", [
            'creditmemoId' => $creditmemoId,
            'orderId' => $orderId,
            'orderToken' => $orderToken,
            'totalRefunded' => $totalRefunded,
            'reason' => $reason,
            'creditmemo' => $creditmemoData,
        ]);

        try {
            $resp = $this->refundOrder($orderToken, $reason, $totalRefunded);
                
            $start_index = strpos($resp, '{');
            $json_string = substr($resp, $start_index);
            $data = json_decode($json_string, true);

            if (isset($data['error'])) {
                
                $this->logger->debug("Order {$orderId} has been error in Refunded", [
                    'orderId' => $orderId,
                    'orderToken' => $orderToken,
                    'response' => $data,
                ]);

                $errorDescription = $data['error']['description'];
                $creditmemo->addComment("Refund Error: $errorDescription");
                $creditmemo->save();

                throw new LocalizedException(__("Refund Error: $errorDescription"));

                return;
            }else{
                $this->logger->debug("Order {$orderId} has been Refunded successfully", [
                    'orderId' => $orderId,
                    'orderToken' => $orderToken,
                    'response' => $data
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logger->critical("Error Refunded order ID: {$orderId}", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ]);
        }
       
    }

    private function refundOrder($orderToken, $reason, $amount)
    {
        $endpoint = "/merchants/orders/{$orderToken}/refund";

        $reason = empty($reason) ? 'Magento Refund' : $reason;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $body = [
            'reason' => $reason,
            'amount' => $amount,
        ];

        $this->logger->debug("Order Token {$orderToken} Refound in Progress...", [
            'endpoint' => $endpoint,
            'headers' => $headers,
            'body' => $body
        ]);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $requestHelper = $objectManager->get(\Deuna\Now\Helper\RequestHelper::class);

        $response = $requestHelper->request($endpoint, 'POST', json_encode($body), $headers);
        
        return $response;
    }
}
