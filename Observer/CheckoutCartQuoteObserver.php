<?php

namespace Deuna\Now\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartQuoteObserver implements ObserverInterface
{
    /**
     *  @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $quote->save();
        $quote->afterSave();
        $quote->beforeSave();
    }
}
