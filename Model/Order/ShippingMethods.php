<?php

namespace Deuna\Now\Model\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Deuna\Now\Api\ShippingMethodsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Webapi\Rest\Request;
use Deuna\Now\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Deuna\Now\Model\OrderTokens;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Country;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

/**
 * Class ShippingMethods
 */
class ShippingMethods implements ShippingMethodsInterface
{
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Shipping method converter
     *
     * @var ShippingMethodConverter
     */

    /**
    * @var DirectoryHelper
    */
    protected $directoryHelper;


    protected $shippingMethod;

    protected $converter;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    protected $shippingMethodManagementInterface;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;


    protected $_scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    private $orderTokens;

    /**
     * @var Collection
     */
    private $regionCollectionFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LogtailHandler
     */
    protected $logger;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     */
    public function __construct(
        \Magento\Quote\Api\Data\ShippingMethodInterface $shippingMethod,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagementInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper,
        JsonFactory $resultJsonFactory,
        Request $request,
        Json $json,
        OrderTokens $orderTokens,
        CollectionFactory $regionCollectionFactory,
        DirectoryHelper $directoryHelper
    ) {
        $this->shippingMethod = $shippingMethod;
        $this->quoteRepository = $quoteRepository;
        $this->converter = $converter;
        $this->storeManager = $storeManager;
        $this->_currency = $currency;
        $this->shippingMethodManagementInterface = $shippingMethodManagementInterface;
        $this->productRepository = $productRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->json = $json;
        $this->orderTokens = $orderTokens;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->directoryHelper = $directoryHelper;
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    /**
     * @param int $cartId
     * @return array|void
     * @throws NoSuchEntityException
     */
    public function get(int $cartId)
    {
        /** @var Quote $quote */
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // Set Shipping Address
        $this->setShippingInfo($quote);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        // Get Shipping Rates
        $shippingRates = $this->getShippingRates($quote);

        $shippingMethods = [
            'shipping_methods' => []
        ];

        $freeShippingMinAmount = $this->getFreeShippingSubtotal();

        $this->logger->debug("Free Shipping Min Amount: {$freeShippingMinAmount}");
        $this->logger->debug("shippingRates", [
            'shippingRates' => $shippingRates,
        ]);

        foreach ($shippingRates as $method) {
            $this->logger->debug("Shipping Method: {$method->getMethodCode()}");

            if($method->getMethodCode() == 'freeshipping') {

                if($freeShippingMinAmount <= $quote->getSubtotal()) {
                    $shippingMethods['shipping_methods'][] = [
                        'code' => $method->getMethodCode(),
                        'name' => $method->getMethodTitle(),
                        'cost' => $this->orderTokens->priceFormat($method->getAmount()),
                        'tax_amount' => $method->getPriceInclTax(),
                        'min_delivery_date' => '',
                        'max_delivery_date' => ''
                    ];
                }
            } else {

                if(!is_null($method->getMethodCode())) {
                    $shippingMethods['shipping_methods'][] = [
                        'code' => $method->getMethodCode(),
                        'name' => $method->getMethodTitle(),
                        'cost' => $this->orderTokens->priceFormat($method->getAmount()),
                        'tax_amount' => $method->getPriceInclTax(),
                        'min_delivery_date' => '',
                        'max_delivery_date' => ''
                    ];
                }
            }
        }

        if (empty($shippingMethods['shipping_methods'])) {
            throw new StateException(__('Verifica tu información de entrega y código postal.'));
        }

        die($this->json->serialize($shippingMethods));
    }



    /**
     * @param int $cartId
     * @param string $code
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function set(int $cartId, string $code) {

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // Get Shipping Rates
        $shippingRates = $this->getShippingRates($quote);

        $shippingAmount = 0;

        foreach ($shippingRates as $shippingMethod) {
            if ($shippingMethod->getMethodCode() == $code) {
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setShippingMethod($shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode());
                $shippingAddress->setShippingDescription($shippingMethod->getCarrierTitle() . ' - ' . $shippingMethod->getMethodTitle());
                $shippingAddress->setShippingAmount($shippingMethod->getAmount());
                $shippingAddress->setCollectShippingRates(true);
                $shippingAddress->save();

                $quote->setShippingAddress($shippingAddress);

                // $shippingAmount = $this->orderTokens->priceFormat($shippingAddress->getShippingAmount());
                $shippingAmount = $this->orderTokens->priceFormat($shippingMethod->getAmount());

                break ;
            }
        }

        $order = $this->orderTokens->getBody($quote);

        if(
            $order['order']['shipping_amount'] !== $shippingAmount ||
            $order['order']['shipping_amount'] > 0
        ) {
            $order['order']['total_amount'] -= $order['order']['shipping_amount'];

            $order['order']['shipping_amount'] = $shippingAmount;

            $order['order']['total_amount'] += $shippingAmount;
        }

        return $this->getJson($order);
    }

    /**
    * Allowed Countries Getter.
    */
   public function getAllowedCountries()
   {
       $countries = [];

       /* @var Country $country */
       foreach ($this->directoryHelper->getCountryCollection() as $country) {
           $countries[] = [
               'value' => $country->getId(),
               'label' => $country->getName()
           ];
       }

       return $countries;
   }

    /**
     * @param $quote
     * @return array
     */
    public function getShippingRates($quote)
    {
        $quote->collectTotals();
        $output = [];

        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getCountryId()) {
            throw new StateException(__('Verifica tu información de entrega y código postal.'));
        }

        $countriesAllowed = $this->getAllowedCountries();
        $this->helper->log('debug','countriesAllowed:', [$countriesAllowed]);
        $isCountryAllowed = false;


        foreach ($countriesAllowed as $country) {
            if ($shippingAddress->getCountryId() == $country['value']) {
                $isCountryAllowed = true;
            }
        }

        if($isCountryAllowed){
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
            $shippingAddress->save();
            $shippingRates = $shippingAddress->getGroupedAllShippingRates();

            foreach ($shippingRates as $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
                }
            }

            return $output;
        }

        return $output;
    }

    private function setShippingInfo($quote)
    {
        try {
            $body = $this->request->getBodyParams();

            $regionId = $this->getRegionId($body['state_name']);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setFirstname($body['first_name']);
            $shippingAddress->setLastname($body['last_name']);
            $shippingAddress->setTelephone($body['phone']);
            $shippingAddress->setStreet($body['address1']);
            $shippingAddress->setCity($body['city']);
            $shippingAddress->setPostcode($body['zipcode']);
            $shippingAddress->setCountryId($body['country_iso']);
            $shippingAddress->setRegionId($regionId);
            $shippingAddress->save();

            $billingAddress = $quote->getBillingAddress();
            $billingAddress->setFirstname($body['first_name']);
            $billingAddress->setLastname($body['last_name']);
            $billingAddress->setTelephone($body['phone']);
            $billingAddress->setStreet($body['address1']);
            $billingAddress->setCity($body['city']);
            $billingAddress->setPostcode($body['zipcode']);
            $billingAddress->setCountryId($body['country_iso']);
            $billingAddress->setRegionId($regionId);
            $billingAddress->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Verifica tu información de entrega y código postal.', $e->getMessage()));
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function getItems($quote)
    {
        $items = [];
        foreach ($quote->getItems() as $item) {
            try {
                $product = $this->productRepository->get($item->getSku());
                $items[] = [
                    "id" => $item->getItemId(),
                    "name" => $item->getName(),
                    "description" => $item->getDescription(),
                    "options" => "",
                    "total_amount" => [
                        "amount" => ($item->getPrice() * $item->getQty()),
                        "original_amount" => ($product->getPrice() * $item->getQty()),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "unit_price" => [
                        "amount" => $item->getPrice(),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "tax_amount" => [
                        "amount" => $quote->getStoreToQuoteRate(),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "currency_symbol" => $this->_currency->getCurrencySymbol()
                    ],
                    "quantity" => $item->getQty(),
                    "uom" => $product->getUom(),
                    "upc" => $product->getUpc(),
                    "sku" => $item->getSku(),
                    "isbn" => $product->getIsbn(),
                    "brand" => $product->getBrand(),
                    "manufacturer" => $product->getManufacturer(),
                    "category" => implode(', ', $product->getCategoryIds()),
                    "color" => $product->getColor(),
                    "size" => $product->getSize(),
                    "weight" => [
                        "weight" => $product->getWeight(),
                        "unit" => $this->_scopeConfig->getValue(
                            'general/locale/weight_unit',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    ],
                    "image_url" => $product->getProductUrl(),
                    "details_url" => "",
                    "type" => $item->getProductType(),
                    "taxable" => (bool)$quote->getStoreToQuoteRate()
                ];
            } catch (\Exception $e) {
                throw new CouldNotSaveException(__('Verifica tu información de entrega y código postal.', $e->getMessage()));
            }
        }

        return $items;
    }

    protected function getShippingAddress($shippingAddress)
    {
        return [
            "id" => $shippingAddress->getId(),
            "user_id" => $shippingAddress->getCustomerId(),
            "first_name" => $shippingAddress->getFirstName(),
            "last_name" => $shippingAddress->getLastName(),
            "phone" => $shippingAddress->getTelephone(),
            "identity_document" => $shippingAddress->getIdentityDocument(),
            "lat" => $shippingAddress->getLat(),
            "lng" => $shippingAddress->getLng(),
            "address1" => $shippingAddress->getStreetLine(1),
            "address2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "zipcode" => $shippingAddress->getPostcode(),
            "state_name" => $shippingAddress->getRegion(),
            "country_code" => $shippingAddress->getCountryId(),
            "additional_description" => $shippingAddress->getAdditionalDescription().' (ShippingMethods)',
            "address_type" => $shippingAddress->getAddressType(),
            "is_default" => (bool)$shippingAddress->getIsDefaultShipping(),
            "created_at" => $shippingAddress->getCreatedAt(),
            "updated_at" => $shippingAddress->getUpdatedAt()
        ];
    }

    /**
     * @param $data
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getJson($data)
    {
        $json = $this->resultJsonFactory->create();

        $json->setData($data);

        return $json;
    }

    private function getFreeShippingSubtotal()
    {
        return $this->_scopeConfig->getValue('carriers/freeshipping/free_shipping_subtotal', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getRegionId($stateName)
    {
        $region = $this->regionCollectionFactory->create()
                  ->addRegionNameFilter($stateName)
                  ->getFirstItem()
                  ->toArray();

        return empty($region) ? 0 : $region['region_id'];
    }
}
