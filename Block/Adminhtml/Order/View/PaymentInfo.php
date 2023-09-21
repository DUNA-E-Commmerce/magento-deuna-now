<?php
namespace Deuna\Now\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Exception\NoSuchEntityException;

class PaymentInfo extends Template
{
    protected $_coreRegistry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    public function getPaymentInfo()
    {
        $order = $this->getOrder();

        try {
            $payment = $order->getPayment();

            $paymentInfo = [
                'method' => $payment->getMethod(),
                'payment_name' => $payment->getAdditionalInformation('payment_name'),
                'processor' => $payment->getAdditionalInformation('processor'),
                'status' => $payment->getAdditionalInformation('status'),
                'method_type' => $payment->getAdditionalInformation('method_type'),
                'card_brand' => $payment->getAdditionalInformation('card_brand'),
                'card_holder' => $payment->getAdditionalInformation('card_holder'),
                'first_six' => $payment->getAdditionalInformation('first_six'),
                'last_four' => $payment->getAdditionalInformation('last_four')
            ];

            return $paymentInfo;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
