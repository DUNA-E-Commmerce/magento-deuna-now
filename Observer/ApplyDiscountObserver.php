<?php
namespace Deuna\Now\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRule\Model\Rule;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;
use Magento\SalesRule\Model\Coupon;

class ApplyDiscountObserver implements ObserverInterface
{
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';

    protected $logger;
    protected $_coupon;

    public function __construct( 
        Coupon $coupon
        ) {
        $this->_coupon = $coupon;
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $couponCode = $quote->getCouponCode();

        if (!empty($couponCode)) {
            $TotalDiscountPercentage = 0;
            $appliedRuleIds = $quote->getAppliedRuleIds();
            $appliedRules = explode(',', $appliedRuleIds);

            $ruleModel = \Magento\Framework\App\ObjectManager::getInstance()->create(Rule::class);

            foreach ($appliedRules as $ruleId) {
                $ruleModel->load($ruleId);
                $TotalDiscountPercentage = $TotalDiscountPercentage + $ruleModel->getDiscountAmount();
            }

            $ruleId2 = $this->_coupon->loadByCode($couponCode)->getRuleId();
            $ruleModel->load($ruleId2);
            $TotalDiscountPercentage = $TotalDiscountPercentage + $ruleModel->getDiscountAmount();

            $this->logger->debug('Total Percentage: ' . $TotalDiscountPercentage);

            $baseSubtotal = $quote->getBaseSubtotal();

            $this->logger->debug('SubTotal Base: ' . $baseSubtotal);

            $discountAmount = ($baseSubtotal * $TotalDiscountPercentage) / 100;

            $this->logger->debug('Discount Amount: ' . $discountAmount);

            $quote->setDiscountAmount($discountAmount);
            $quote->setBaseDiscountAmount($discountAmount);

            $grandTotal = $quote->getGrandTotal() - $discountAmount;
            $baseGrandTotal = $quote->getBaseGrandTotal() - $discountAmount;

            $this->logger->debug('Grand Total: ' . $grandTotal);
            $this->logger->debug('Base Grand Total: ' . $baseGrandTotal);

            $quote->setGrandTotal($grandTotal);
            $quote->setBaseGrandTotal($baseGrandTotal);

            $quote->setSubtotal($baseSubtotal);
            $quote->setBaseSubtotal($baseSubtotal);

            // $quote->save();

        }
    }
}
