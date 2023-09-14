<?php

namespace Deuna\Now\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Deuna\Now\Helper\Data;
use Exception;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Deuna\Now\Helper\LogtailHelper as Logger;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderTokens
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var Category
     */
    private $category;
    
    private $coupon;

    private $saleRule;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Image
     */
    protected $imageHelper;

    public function __construct(
        Session $checkoutSession,
        Curl $curl,
        Json $json,
        Data $helper,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        Category $category,
        EncryptorInterface $encryptor,
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\Rule $saleRule,
        \Magento\Framework\Event\Observer $observer,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement,
        \Magento\Quote\Model\ShippingMethodManagement $shippingMethodManager,
        AddressRepositoryInterface $addressRepository,
        ShippingAssignmentInterface $shippingAssignment,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TotalsInformationInterface $totalsInformationInterface,
        TotalsInformationManagementInterface $totalsInformationManagementInterface,
        Image $imageHelper,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->curl = $curl;
        $this->json = $json;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->category = $category;
        $this->encryptor = $encryptor;
        $this->coupon = $coupon;
        $this->saleRule = $saleRule;
        $this->observer = $observer;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->addressRepository = $addressRepository;
        $this->shippingAssignment = $shippingAssignment;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->totalsInformationInterface = $totalsInformationInterface;
        $this->totalsInformationManagementInterface = $totalsInformationManagementInterface;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param $addressId
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressData($addressId)
    {
        $addressData = null;

        try {
            $addressData = $this->addressRepository->getById($addressId);
        } catch (\Exception $e) {
            $this->logger->error('Critical error in '.__CLASS__.'\\'.__FUNCTION__, [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ]);
        }
        return $addressData;
    }

    /**
     * @param TotalsInformationManagementInterface $subject
     * @param int                                  $cartId
     * @param TotalsInformationInterface           $addressInformation
     *
     * @return mixed[]|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function AfterCalculate(
        TotalsInformationManagementInterface $subject,
        int $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        $this->logger->debug('AfterCalculate Method', [
            'cartId' => $cartId,
            'subject' => $subject,
            'addressInformation' => $addressInformation,
        ]);

        return null;
    }

    /**
     * @return array
     */
    public function getBody($quote): array
    {
        $totals = $quote->getSubtotalWithDiscount();
        $domain = $this->storeManager->getStore()->getBaseUrl();
        $stores = [];

        $discounts = $this->getDiscounts($quote);

        $tax_amount = $quote->getShippingAddress()->getBaseTaxAmount();

        /**
         * Initial Data for Delivery Methods
         */
        $shippingAddress = $quote->getShippingAddress();
        $shippingMethod =  $shippingAddress->getShippingMethod();

        $shippingMethodSelected = "delivery";
        $nameStore = "";
        $addressStore = "";
        $lat = 0;
        $long = 0;

        $this->logger->debug("Shipping method {$shippingMethod} selected");

        $discount_amount = $this->getDiscountAmount($quote);
        $subtotal_amount = $quote->getSubtotal();
        $subtotal_amount -= $discount_amount;
        $totals += $tax_amount;

        $body = [
            'order' => [
                'order_id' => $quote->getId(),
                'currency' => $quote->getCurrency()->getQuoteCurrencyCode(),
                'tax_amount' => $this->priceFormat($tax_amount),
                'total_tax_amount' => $this->priceFormat($tax_amount),
                'items_total_amount' => $this->priceFormat($totals),
                'sub_total' => $this->priceFormat($subtotal_amount),
                'total_amount' => $this->priceFormat($totals),
                'total_discount' => $this->priceFormat($discount_amount),
                'store_code' => 'all', //$this->storeManager->getStore()->getCode(),
                'items' => $this->getItems($quote),
                'discounts' => $discounts ? [$discounts] : [],
                'shipping_options' => [
                    'type' => $shippingMethodSelected,
                    'details' => [
                        'store_name' => $nameStore,
                        'address' =>  $addressStore,
                        'address_coordinates' => [
                            'lat' => $lat,
                            'lng' => $long
                        ],
                        'contact' => [
                            'name' => $nameStore
                        ],
                    ]
                ],
                'redirect_url' => $domain . 'checkout/onepage/success',
                'webhook_urls' => [
                    'notify_order' => $domain . 'rest/V1/orders/notify',
                    'shipping_rate' => '',
                ]
            ]
        ];
       return $this->getShippingData($body, $quote, $stores);
    }

    /**
     * @param $quote
     * @return array|void
     */
    private function getDiscounts($quote)
    {
        $coupon = $quote->getCouponCode();
        if ($coupon) {
            $subTotalWithDiscount = $quote->getSubtotalWithDiscount();
            $subTotal = $quote->getSubtotal();
            $couponAmount = $subTotal - $subTotalWithDiscount;

            $ruleId = $this->coupon->loadByCode($coupon)->getRuleId();
            $rule = $this->saleRule->load($ruleId);
            $freeShipping = $rule->getSimpleFreeShipping();

            $discount = [
                'amount' => $this->priceFormat($couponAmount),
                'code' => $coupon,
                'reference' => $coupon,
                'description' => '',
                'details_url' => '',
                'free_shipping' => [
                    'is_free_shipping' => (bool) $freeShipping,
                    'maximum_cost_allowed' => 100
                ],
                'discount_category' => 'coupon'
            ];
            return $discount;
        }
    }

    /**
     * Get Discount Amount
     * @param $quote
     * @return int
     */
    private function getDiscountAmount($quote)
    {
        $subTotalWithDiscount = $quote->getSubtotalWithDiscount();
        $subTotal = $quote->getSubtotal();
        $couponAmount = $subTotal - $subTotalWithDiscount;
        return $couponAmount;
    }

    /**
     * @param $items
     * @return array
     */
    private function getItems($quote): array
    {
        $currencyCode = $quote->getCurrency()->getQuoteCurrencyCode();
        $currencySymbol = $this->priceCurrency->getCurrencySymbol();
        $items = $quote->getItemsCollection();
        $itemsList = [];
        foreach ($items as $item) {
            if ($item->getParentItem()) continue;
            $qtyItem = (int) $item->getQty();
            $totalSpecialItemPrice = $item->getPrice('special_price')*$qtyItem;
            $totalRegularItemPrice = $item->getProduct()->getPrice('regular_price')*$qtyItem;
            $itemsList[] = [
                'id' => $item->getProductId(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'options' => '',
                'total_amount' => [
                    'amount' => $this->priceFormat($totalSpecialItemPrice),
                    'original_amount' => $this->priceFormat($totalRegularItemPrice),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'unit_price' => [
                    'amount' => $this->priceFormat($item->getProduct()->getPrice('regular_price')),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'tax_amount' => [
                    'amount' => $this->priceFormat($item->getTaxAmount()),
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol
                ],
                'quantity' => $qtyItem,
                'uom' => '',
                'upc' => '',
                'sku' => $item->getProduct()->getSku(),
                'isbn' => '',
                'brand' => '',
                'manufacturer' => '',
                'category' => $this->getCategory($item),
                'color' => '',
                'size' => '',
                'weight' => [
                    'weight' => $this->priceFormat($item->getWeight(), 2, '.', ''),
                    'unit' => $this->getWeightUnit()
                ],
                'image_url' => $this->getImageUrl($item),
                'type' => ($item->getIsVirtual() ? 'virtual' : 'physical'),
                'taxable' => true
            ];
        }

        return $itemsList;
    }

    /**
     * @param $order
     * @param $shippingAmount
     * @return array
     */
    private function getShippingData($order, $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        $shippingAmount = $this->priceFormat($shippingAddress->getShippingAmount());

        $address = $shippingAddress->getStreet();

        $address_1 = "";
        $address_2 = "";

        if (isset($address) && is_array($address) && array_key_exists(0, $address)) {
            $address_1 =  $address[0];
        }

        if (isset($address) && is_array($address) && array_key_exists(1, $address)) {
            $address_2 =  $address[1];
        }

        $order['order']['shipping_address'] = [
            'id' => 0,
            'user_id' => (string) 0,
            'first_name' => $shippingAddress->getFirstname(),
            'last_name' => $shippingAddress->getLastname(),
            'phone' => $shippingAddress->getTelephone(),
            'identity_document' => '',
            'lat' => 0,
            'lng' => 0,
            'address1' => $address_1,
            'address2' => $address_2,
            'city' => $shippingAddress->getCity(),
            'zipcode' => $shippingAddress->getPostcode(),
            'state_name' => $shippingAddress->getRegion(),
            'country_code' => $shippingAddress->getCountryId(),
            'additional_description' => '',
            'address_type' => 'home',
            'is_default' => false,
            'created_at' => '',
            'updated_at' => '',
        ];

        $billingAddress = $quote->getBillingAddress();


        $address = $billingAddress->getStreet();
        $baddress_1 = "";
        $baddress_2 = "";


        if (isset($address) && is_array($address) && array_key_exists(0, $address)) {
            $baddress_1 =  $address[0];
        }

        if (isset($address) && is_array($address) && array_key_exists(1, $address)) {
            $baddress_2 =  $address[1];
        }

        $order['order']['billing_address'] = [
            'first_name' => $billingAddress->getFirstname(),
            'last_name' => $billingAddress->getLastname(),
            'phone' => $billingAddress->getTelephone(),
            'identity_document' => '',
            'lat' => 0,
            'lng' => 0,
            'address1' => $baddress_1,
            'address2' => $baddress_2,
            'city' => $billingAddress->getCity(),
            'zipcode' => $billingAddress->getPostcode(),
            'state_name' => $billingAddress->getRegion(),
            'country_code' => $billingAddress->getCountryId(),
            'country' => $billingAddress->getCountryId(),
            'additional_description' => '',
            'address_type' => 'home',
            'is_default' => false,
            'created_at' => '',
            'updated_at' => '',
            'email' => $billingAddress->getEmail(),
        ];

        $order['order']['status'] = 'pending';
        $order['order']['shipping_amount'] = $shippingAmount;
        $order['order']['sub_total'] += $shippingAmount;
        $order['order']['total_amount'] += $shippingAmount;

        return $order;
    }

    /**
     * @param $price
     * @return int
     */
    public function priceFormat($price): int
    {
        $priceFix = number_format(is_null($price) ? 0 : $price, 2, '.', '');

        return (int) round($priceFix * 100, 1 , PHP_ROUND_HALF_UP);
    }

    /**
     * @return string
     */
    private function getWeightUnit(): string
    {
        return $this->helper->getConfigValue('general/locale/weight_unit');
    }

    /**
     * @param $item
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getImageUrl($item): string
    {
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($item->getProduct()->getSku());

        $image = $product->getMediaGalleryImages()->getFirstItem();

        if ($image->getMediaType() === 'image') {
            return $this->imageHelper
                ->init($product, 'product_page_image_small')
                ->setImageFile($image->getFile())
                ->getUrl();
        }

        return $this->imageHelper->init($product, 'product_page_image_small')->getUrl();
    }

    /**
     * @param $item
     * @return string
     */
    private function getCategory($item): string
    {
        $categoriesIds = $item->getProduct()->getCategoryIds();
        foreach ($categoriesIds as $categoryId) {
            $category = $this->category->load($categoryId)->getName();
        }
        return $category;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    private function tokenize(): array
    {

        try {

            $quote = $this->checkoutSession->getQuote();

            $body = $this->json->serialize($this->getBody($quote));

            $body = json_encode($this->getBody($quote));

            $endpoint = '/merchants/orders'; 

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $requestHelper = $objectManager->get(\Deuna\Now\Helper\RequestHelper::class);
            

            $response = $requestHelper->request($endpoint, 'POST', $body);
            
            list(, $response) = explode("\r\n\r\n", $response, 2);

            $response = json_decode($response, true);

            if ($response === null && json_last_error() !== JSON_ERROR_NONE) {
                die('Error al decodificar JSON');
            }

            if (!empty($response['error'])) {
                $quote->setIsActive(false);
                $quote->save();

                throw new LocalizedException(__($response['error']['description']));
            }

            return $response;

        } catch (Exception $e) {

            var_dump($e);die;
        }
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getToken()
    {
        try {
            $this->logger->info('Starting tokenization');

            $this->getPaymentMethodList();

            $token = $this->tokenize();

            $this->logger->info("Token Generated ({$token['token']})", [
                'token' => $token,
            ]);

            return $token;
        } catch(NoSuchEntityException $e) {
            $err = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ];
            $this->logger->error('Critical error in '.__FUNCTION__, $err);

            return $err;
        } catch(Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ];
            $this->logger->error('Critical error in '.__FUNCTION__, );

            return $err;
        }
    }

    private function getPaymentMethodList()
    {
        $objectManager = ObjectManager::getInstance();
        $scope = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $methodList = $scope->getValue('payment');

        $output = [];

        foreach( $methodList as $code => $_method )
        {
            if( isset($_method['active']) && $_method['active'] == 1 ) {
                $output[] = [
                    'code' => $code,
                    'method' => $_method,
                ];
            }
        }

        $this->logger->debug('Payment Method List', $output);
    }

}
