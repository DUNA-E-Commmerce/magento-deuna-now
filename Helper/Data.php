<?php

namespace Deuna\Now\Helper;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * constant
     */
    const XML_PATH_DUNA = 'payment/deuna/';
    const MODE_PRODUCTION = 2;
    const MODE_STAGING = 1;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var CollectionFactory
     */
    private $regionCollectionFactory;


    public function __construct(
        Context $context,
        ResourceConnection $resource,
        CollectionFactory $regionCollectionFactory
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->regionCollectionFactory = $regionCollectionFactory;
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
     * @param $message
     * @param $type
     * @param array $context
     * @return void
     */
    public function log($type, $message, array $context = []) {
        return $message;
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

        $sql = "UPDATE sales_order_payment
                SET method = '$paypalCode'
                WHERE entity_id = $id";

        $connection->query($sql);

        return $sql;
    }

    /**
     * Get the region ID based on the state name.
     *
     * @param string $stateName The name of the state.
     * @return int The ID of the region corresponding to the state, or 0 if not found.
     */
    public function getRegionId($stateName)
    {
        $region = $this->regionCollectionFactory->create()
                  ->addRegionNameFilter($stateName)
                  ->getFirstItem()
                  ->toArray();

        return empty($region) ? 0 : $region['region_id'];
    }
    
}
