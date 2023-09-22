<?php

namespace Deuna\Now\Model;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteFactory as Quote;
use Magento\Quote\Api\CartRepositoryInterface as CRI;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Deuna\Now\Helper\Data;
use Deuna\Now\Model\CreateInvoice;
use Deuna\Now\Model\OrderTokens;
use Deuna\Now\Helper\LogtailHelper as Logger;
use Exception;

class PostManagement
{
    const TRANSACTION_TYPES = [
        'approved' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
        'auth' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
        'capture' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
        'refund' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
        'void' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
        'cancel' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
    ];

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'deuna';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderTokens
     */
    private $orderTokens;

    /**
     * @var CRI
     */
    protected $cri;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(
        Request $request,
        QuoteManagement $quoteManagement,
        OrderTokens $orderTokens,
        CRI $cri,
        Data $helper,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        Logger $logger
    ) {
        $this->request = $request;
        $this->quoteManagement = $quoteManagement;
        $this->orderTokens = $orderTokens;
        $this->cri = $cri;
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Handle order notification from an external payment processor.
     *
     * @return string JSON-encoded response indicating the status of the notification.
     */
    public function notify()
    {
        try {
            $bodyReq = $this->request->getBodyParams();
            $output = [];

            $this->logger->debug('Notify Payload: ', $bodyReq);

            $order = $bodyReq['order'];
            $orderId = $order['order_id'];
            $payment_status = $order['payment_status'];
            $paymentData = $order['payment']['data'];
            $email = $paymentData['customer']['email'];
            $token = $order['token'];
            $paymentProcessor = $paymentData['processor'];
            $paymentMethod = $order['payment_method'];
            $userComment = $order['user_instructions'];
            $shippingAmount = $order['shipping_amount'] / 100;
            $totalAmount = $order['total_amount'] / 100;

            $quote = $this->quotePrepare($order, $email);

            $active = $quote->getIsActive();

            $output = [];

            if ($active) {
                $invoice_status = 1;

                $this->logger->debug("Quote ({$quote->getId()}) is active", [
                    'processor' => $paymentProcessor,
                    'paymentStatus' => $payment_status,
                    'paymentMethod' => $paymentMethod,
                ]);

                if ($paymentMethod != 'cash') {
                    if ($payment_status != 'processed' && $payment_status != 'authorized')
                        return;

                    if ($payment_status == 'processed') {
                        $invoice_status = 2;
                    }
                }

                $mgOrder = $this->quoteManagement->submit($quote);


                $this->logger->debug("Order created with status {$mgOrder->getState()}");

                if (!empty($userComment)) {
                    $mgOrder->addStatusHistoryComment(
                        "Comentario de cliente<br>
                        <i>{$userComment}</i>"
                    )->setIsVisibleOnFront(true);
                }

                $mgOrder->setShippingAmount($shippingAmount);
                $mgOrder->setBaseShippingAmount($shippingAmount);
                $mgOrder->setGrandTotal($totalAmount);
                $mgOrder->setBaseGrandTotal($totalAmount);

                $this->updatePaymentState($mgOrder, $payment_status, $totalAmount);

                $banco_emisor = isset($paymentData['from_card']['bank']) ? $paymentData['from_card']['bank'] : '';
                $country_iso = isset($paymentData['from_card']['country_iso']) ? $paymentData['from_card']['country_iso'] : '';

                $payment = $mgOrder->getPayment();
                $payment->setAdditionalInformation('processor', $paymentProcessor);
                $payment->setAdditionalInformation('card_type', $paymentData['from_card']['card_brand']);
                $payment->setAdditionalInformation('banco_emisor', $banco_emisor);
                $payment->setAdditionalInformation('country_iso', $country_iso);
                $payment->setAdditionalInformation('card_bin', $paymentData['from_card']['first_six']);
                $payment->setAdditionalInformation('auth_code', $paymentData['external_transaction_id']);
                $payment->setAdditionalInformation('payment_method', $paymentMethod);
                $payment->setAdditionalInformation('number_of_installment', $paymentData['installments']);
                $payment->setAdditionalInformation('deuna_payment_status', $payment_status);
                $payment->setAdditionalInformation('authentication_method', $paymentData['authentication_method']);
                $payment->setAdditionalInformation('token', $token);
                $payment->save();

                $mgOrder->save();

                $newOrderId = $mgOrder->getIncrementId();

                $this->logger->debug("Order ({$newOrderId}) saved");

                $output = [
                    'status' => $order['status'],
                    'data' => [
                        'order_id' => $newOrderId,
                    ]
                ];

                $this->logger->info("Pedido ({$newOrderId}) notificado satisfactoriamente", [
                    'response' => $output,
                ]);

                ObjectManager::getInstance()->create(CreateInvoice::class)->execute($mgOrder->getId(), $invoice_status);

                if ($paymentProcessor == 'paypal_commerce') {
                    $paypalChanged = $this->helper->savePaypalCode($payment->getId());

                    $this->logger->debug("Paypal code saved", [
                        'paypalChanged' => $paypalChanged,
                    ]);
                }

                echo json_encode($output);

                die();
            } else {
                $output = [
                    'status' => 'failed',
                    'data' => 'Quote is not active',
                ];

                $this->logger->warn("Pedido ({$orderId}) no se pudo notificar", [
                    'data' => $output,
                ]);

                return json_encode($output);
            }
        } catch (Exception $e) {
            $err = [
                'payload' => $bodyReq,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];

            $this->logger->error('Critical error in ' . __CLASS__ . '\\' . __FUNCTION__, $err);

            return [
                "status" => 'failed',
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "data" => [
                    "order_id" => $orderId
                ]
            ];
        }
    }

    /**
     * Prepare a quote object for order processing.
     *
     * @param array $order The order data.
     * @param string $email The customer's email address.
     * @return Quote The prepared quote object.
     */
    private function quotePrepare($order, $email)
    {
        $quoteId = $order['order_id'];

        $quote = $this->cri->get($quoteId);

        $paymentData = $order['payment']['data'];
        $processor = $paymentData['processor'];

        $this->logger->debug("Payment Method: {$processor}");


        if (isset($paymentData['authentication_method'])) {
            if (!empty($paymentData['authentication_method']) && $processor == 'evopayment')
                $processor = "{$processor}_3ds";
        }

        $this->logger->debug("Payment Method: {$processor}", $paymentData);

        $payment = $quote->getPayment();

        $payment->setMethod('deuna');
        $payment->setAdditionalInformation('payment_name', 'Deuna');
        $payment->setAdditionalInformation('processor', $processor);
        $payment->setAdditionalInformation('status', $paymentData['status']);
        $payment->setAdditionalInformation('method_type', $paymentData['method_type']);
        $payment->setAdditionalInformation('card_brand', $paymentData['from_card']['card_brand']);
        $payment->setAdditionalInformation('card_holder', $paymentData['from_card']['card_holder']);
        $payment->setAdditionalInformation('first_six', $paymentData['from_card']['first_six']);
        $payment->setAdditionalInformation('last_four', $paymentData['from_card']['last_four']);
        $payment->save();

        $quote->setCustomerFirstname($order['shipping_address']['first_name']);
        $quote->setCustomerLastname($order['shipping_address']['last_name']);
        $quote->setCustomerEmail($email);

        $this->updateAddresses($quote, $order);

        $this->setCustomer($order, $email);

        return $quote;
    }

    /**
     * Set or create a customer based on the provided email address.
     *
     * @param array $order The order data.
     * @param string $email The customer's email address.
     * @return Customer|null The customer object if created or found, or null if email is empty.
     */
    private function setCustomer($order, $email)
    {
        if (!empty($email)) {
            $store = $this->storeManager->getStore();
            $websiteId = $store->getStoreId();

            $customer = $this->customerFactory->create();

            $customer->setWebsiteId($websiteId)->loadByEmail($email);

            if (!$customer->getId()) {
                // If not avilable then create this customer
                $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($order['shipping_address']['first_name'])
                    ->setLastname($order['shipping_address']['last_name'])
                    ->setEmail($email)
                    ->setPassword($email);
                $customer->save();
            }

            $customer = $this->customerRepository->getById($customer->getEntityId());

            return $customer;
        } else {
            return null;
        }
    }

    /**
     * Generate and return a token for order processing.
     *
     * @return string JSON-encoded response containing the order token or an error message.
     */
    public function getToken()
    {
        $tokenResponse = $this->orderTokens->getToken();

        if (!empty($tokenResponse['error'])) {
            return json_encode($tokenResponse);
        }

        $json = [
            'orderToken' => $tokenResponse['token'],
            'order_id' => $tokenResponse['order']['order_id'],
        ];

        return json_encode($json);
    }

    /**
     * Update the payment state and status of an order based on the payment status.
     *
     * @param Order $order The order to update.
     * @param string $payment_status The payment status ('processed' or 'authorized').
     * @param float $totalAmount The total amount paid.
     * @return void
     */
    public function updatePaymentState($order, $payment_status, $totalAmount)
    {
        $payment = $order->getPayment();

        if ($payment_status == 'processed') {
            $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $order->setState($orderState)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->setTotalPaid($totalAmount);

            $this->logger->debug("Order ({$order->getIncrementId()}) status changed to PROCESSING");

            $this->createTransaction($payment, 'approved');
        } elseif ($payment_status == 'authorized') {
            $orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order->setState($orderState)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);

            $this->logger->debug("Order ({$order->getIncrementId()}) status changed to PENDING PAYMENT");

            $this->createTransaction($payment, 'auth');
        }
    }

    /**
     * Update the billing and shipping addresses of a quote with provided address data.
     *
     * @param Quote $quote The quote to update.
     * @param array $data The address data containing shipping and billing information.
     * @return void
     */
    public function updateAddresses($quote, $data)
    {
        $shippingData = $data['shipping_address'];
        $billingData = $data['billing_address'];

        //  Billing Address
        $billingRegionId = $this->helper->getRegionId($billingData['state_name']);

        $billing_address = [
            'firstname' => $billingData['first_name'],
            'lastname' => $billingData['last_name'],
            'street' => $billingData['address1'] . ', ' . $billingData['address2'],
            'city' => $billingData['city'],
            'country_id' => $billingData['country_code'],
            'region' => $billingRegionId,
            'postcode' => $billingData['zipcode'],
            'telephone' => $billingData['phone'],
        ];

        $quote->getBillingAddress()->addData($billing_address);

        // Shipping Address
        $shippingRegionId = $this->helper->getRegionId($shippingData['state_name']);

        $shipping_address = [
            'firstname' => (empty($shippingData['first_name']) ? $billingData['first_name'] : $billingData['first_name']),
            'lastname' => (empty($shippingData['last_name']) ? $billingData['last_name'] : $billingData['last_name']),
            'street' => (empty($shippingData['address1']) ? $billingData['address1'] : $shippingData['address1']) . ', ' . (empty($shippingData['address2']) ? $billingData['address2'] : $shippingData['address2']),
            'city' => (empty($shippingData['city']) ? $billingData['city'] : $shippingData['city']),
            'country_id' => (empty($shippingData['country_code']) ? $billingData['country_code'] : $shippingData['country_code']),
            'region' => (empty($shippingRegionId) ? $billingRegionId : $shippingRegionId),
            'postcode' => (empty($shippingData['zipcode']) ? $billingData['zipcode'] : $shippingData['zipcode']),
            'telephone' => (empty($shippingData['phone']) ? $billingData['zipcode'] : $shippingData['phone']),
        ];

        $quote->getShippingAddress()->addData($shipping_address);
    }

    /**
     * Check if a given status is considered a successful payment status.
     *
     * @param string $status The payment status to check.
     * @return bool True if the status is successful, false otherwise.
     */
    public function isSuccessStatus($status)
    {
        switch ($status) {
            case 'processed':
            case 'authorized':
            case 'captured':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Capture a payment transaction for a given order.
     *
     * @param int $orderId The ID of the order to capture the transaction for.
     * @return array|string An array containing capture information or an error message if capture fails.
     */
    public function captureTransaction($orderId)
    {
        try {
            $this->logger->info('Capture Transaction', [
                'orderId' => $orderId,
            ]);

            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            $amount = $payment->getAmountAuthorized();

            $invoiceData = $order->getInvoiceCollection();

            $invoiceData = $invoiceData->getData();

            $this->logger->info('Invoice State', $invoiceData);

            return $this->capturePayment($payment, $amount);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ];
            $this->logger->critical($err['message'], $err);

            return $err;
        }
    }

    /**
     * Capture a payment transaction with the Deuna payment processor.
     *
     * @param Payment $payment The payment object.
     * @return string The response from the capture request.
     */
    public function captureDeuna($payment)
    {

        $orderToken = $payment->getAdditionalInformation('token');

        $endpoint = "/merchants/orders/{$orderToken}/capture";

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $body = [
            'amount' => $this->helper->priceFormat($payment->getAmountAuthorized()),
        ];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $requestHelper = $objectManager->get(\Deuna\Now\Helper\RequestHelper::class);

        $response = $requestHelper->request($endpoint, 'POST', json_encode($body), $headers);

        return $response;
    }

    /**
     * Capture a payment for the given amount using the Deuna payment processor.
     *
     * @param Payment $payment The payment object.
     * @param float $amount The amount to capture.
     * @return array|string The response from the capture request or an error message.
     * @throws \Magento\Framework\Exception\LocalizedException If the amount is invalid.
     */
    public function capturePayment($payment, $amount)
    {
        if ($amount <= 0) {
            $this->logger->error('Invalid amount for capture.');
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for capture.'));
        }

        try {
            $deunaCaptureResponse = $this->captureDeuna($payment);

            if ($deunaCaptureResponse) {
                $status = 'processed';

                // Generate the transaction ID for the capture
                $parentId = "auth-{$payment->getId()}";

                $additionalInfo = [
                    'captured_amount' => $amount,
                    'processor' => $payment->getAdditionalInformation('processor'),
                    'card_type' => $payment->getAdditionalInformation('card_type'),
                    'card_bin' => $payment->getAdditionalInformation('card_bin'),
                    'auth_code' => $payment->getAdditionalInformation('auth_code'),
                    'payment_method' => $payment->getAdditionalInformation('payment_method'),
                    'number_of_installment' => $payment->getAdditionalInformation('number_of_installment'),
                    'deuna_payment_status' => $payment->getAdditionalInformation('deuna_payment_status'),
                    'token' => $payment->getAdditionalInformation('token'),
                ];

                $this->createTransaction($payment, 'capture', $parentId, $amount, $additionalInfo);

                $order = $payment->getOrder();

                $totalPaid = $order->getTotalPaid() + $amount;
                $order->setTotalPaid($totalPaid);

                $totalDue = $order->getGrandTotal() - $totalPaid;
                $order->setTotalDue($totalDue);

                // Update the order state to "Processing"
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->addStatusToHistory(
                        \Magento\Sales\Model\Order::STATE_PROCESSING,
                        __('Payment captured successfully.')
                    )->save();

                return $deunaCaptureResponse;
            } else {
                $this->logger->error('Error capturing payment');

                return $deunaCaptureResponse;
            }
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ];

            $this->logger->critical('Error capturing payment', $err);

            return $err;
        }
    }

    /**
     * Create and save a new transaction for the payment.
     *
     * @param Payment $payment The payment object.
     * @param string $type The type of transaction (e.g., 'approved', 'auth', 'capture').
     * @param string|null $parentId The parent transaction ID for related transactions (only for 'capture').
     * @param float $amount The transaction amount (only for 'capture').
     * @param array $additionalInfo Additional information to store with the transaction.
     */
    public function createTransaction($payment, $type = 'approved', $parentId = null, $amount = 0, $additionalInfo = [])
    {
        $txnId = "{$type}-{$payment->getId()}";
        $txnType = self::TRANSACTION_TYPES[$type];
        $order = $payment->getOrder();

        $payment->setTransactionId($txnId);

        $transaction = $payment->addTransaction($txnType);

        switch ($type) {
            case 'approved':
                $this->logger->debug('Transaction type: approved', [
                    'parentId' => $parentId,
                    'amount' => $amount,
                    'additionalInfo' => $additionalInfo,
                ]);
                $transaction->setIsClosed(1);

                break;
            case 'auth':
                $this->logger->debug('Transaction type: auth', [
                    'parentId' => $parentId,
                    'amount' => $amount,
                    'additionalInfo' => $additionalInfo,
                ]);
                $transaction->setIsClosed(0);

                $payment->setAmountAuthorized($order->getTotalDue());

                break;
            case 'capture':
                $this->logger->debug('Transaction type: capture', [
                    'parentId' => $parentId,
                    'amount' => $amount,
                    'additionalInfo' => $additionalInfo,
                ]);
                $transaction->setIsClosed(1);
                $transaction->setParentTxnId($parentId);
                $transaction->setAmountCaptured($amount);

                $parent = $payment->getAuthorizationTransaction();
                $parent->setIsClosed(1);
                $parent->save();

                $payment->setParentTransactionId($parentId);
                $payment->setAdditionalInformation('deuna_payment_status', 'processed');

                break;
        }

        $transaction->setAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $additionalInfo
        );

        $transaction->save();
        $payment->save();
    }
    
}
