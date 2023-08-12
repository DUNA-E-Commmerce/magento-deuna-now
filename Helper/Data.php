<?php

namespace Deuna\Now\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Deuna\Now\Logger\Logger;

class Data extends AbstractHelper
{
    /**
     * constant
     */
    const XML_PATH_DUNA = 'payment/deuna/';
    const MODE_PRODUCTION = 2;
    const MODE_STAGING = 1;

    /**
     * Logger instance
     * @var Logger
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(
        Context $context,
        Logger $logger,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * @param $field
     * @param $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param $code
     * @param $storeId
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_DUNA . $code, $storeId);
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        // $mode = $this->getGeneralConfig('mode');
        // if ($mode == self::MODE_PRODUCTION) {
        //     $env = 'production';
        // }
        // if ($mode == self::MODE_STAGING) {
        //     $env = 'staging';
        // }
        // return $env;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $domain = $storeManager->getStore()->getBaseUrl();

        switch($domain) {
            case str_contains($domain, 'dev.'):
                return 'develop';
                break;
            case str_contains($domain, 'local.'):
                return 'develop';
                break;
            case str_contains($domain, 'stg.'):
                return 'staging';
                break;
            case str_contains($domain, 'mcstaging.'):
                return 'staging';
                break;
            default:
                return 'production';
                break;
        }
    }

    /**
     * Logger instance
     * @param $message
     * @param $type
     * @param array $context
     * @return void
     */
    public function log($type, $message, array $context = []) {
        $this->logger->{$type}($message, $context);
    }

    /**
     * @param $price
     * @return int
     */
    public function priceFormat($price): int
    {
        $priceFix = number_format(is_null($price) ? 0 : $price, 2, '.', '');

        return (int) round($priceFix * 100, 1 , PHP_ROUND_HALF_UP);;
    }

    public function savePaypalCode($id)
    {
        $paypalCode = 'paypal_express';
        $output = null;
        $connection  = $this->resource->getConnection();

        // $tableName = $connection->getTableName('sales_order_payment');
        // $data = [
        //     'method' => $paypalCode,
        // ];
        // $where = [
        //     'entity_id = ?' => (int)$id,
        // ];

        $sql = "UPDATE sales_order_payment
                SET method = '$paypalCode'
                WHERE entity_id = $id";

        $connection->query($sql);

        return $sql;
    }
}
