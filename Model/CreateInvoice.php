<?php
namespace Deuna\Now\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class CreateInvoice
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;

    const STATE_OPEN = 1;
    const STATE_PAID = 2;
    const STATE_CANCELED = 3;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * Execute the invoice creation process for a given order.
     *
     * @param int $orderId The ID of the order to invoice.
     * @param string $state The state to set for the invoice (default is self::STATE_OPEN).
     * @return void
     */
    public function execute($orderId, $state = self::STATE_OPEN)
    {
        $order = $this->orderRepository->get($orderId);

        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->setState($state);
            $invoice->save();

            $transactionSave = $this->transaction
                                    ->addObject($invoice)
                                    ->addObject($invoice->getOrder());
            $transactionSave->save();

            $this->invoiceSender->send($invoice);

            $order->addCommentToStatusHistory(__('Factura #%1 creada satisfactoriamente.', $invoice->getId()))
                  ->setIsCustomerNotified(true)
                  ->save();
        }
    }
}
