<?php

namespace Deuna\Now\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogtailHelper extends AbstractHelper
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $handler = new StreamHandler(BP . '/var/log/deuna.log', Logger::DEBUG);
        $this->logger = new Logger('deuna');
        $this->logger->pushHandler($handler);
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

    public function sendLogToMagentoLog($message, $level = Logger::DEBUG)
    {
        $this->logger->log($level, $message);
    }
}
