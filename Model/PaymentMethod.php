<?php

namespace Deuna\Now\Model;

/**
 * MD Custom Payment Method Model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Payment Method code
     *
     * @var string
     */
    protected $_code = 'deuna';
}
