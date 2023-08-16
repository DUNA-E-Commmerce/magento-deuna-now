<?php

namespace Deuna\Now\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

class LogtailHelper extends AbstractHelper
{
    /**
     * constant
     */
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';

    /**
     * Logger instance
     * @var Logger
     */
    protected $logger;

    public function __construct(
        Context $context,
    ) {
        parent::__construct($context);
        
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    public function info($message, $data = [])
    {
        $this->logger->info($message, $data);
    }

    public function debug($message, $data = [])
    {
        $this->logger->debug($message, $data);
    }

    public function error($message, $data = [])
    {
        $this->logger->error($message, $data);
    }

    public function notice($message, $data = [])
    {
        $this->logger->notice($message, $data);
    }

    public function warn($message, $data = [])
    {
        $this->logger->warning($message, $data);
    }

    public function critical($message, $data = [])
    {
        $this->logger->critical($message, $data);
    }

    public function alert($message, $data = [])
    {
        $this->logger->alert($message, $data);
    }

    public function emergency($message, $data = [])
    {
        $this->logger->emergency($message, $data);
    }
}
