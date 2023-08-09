<?php

namespace Deuna\Now\Api\Data;

interface CheckoutInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    /**
     * Set user_token
     *
     * @param string $user_token user_token
     *
     * @return string
     */
    public function setUserToken($user_token);

    /**
     * Get UserToken
     *
     * @return string|null
     */
    public function getUserToken();

    /**
     * Set setCartId
     *
     * @param int $cart_id cart_id
     *
     * @return int
     */
    public function setCartId($cart_id);
    /**
     * Get setType
     *
     * @return int|null
     */
    public function getCartId();

    /**
     * Set store_id
     *
     * @param int $store_id store_id
     *
     * @return int
     */
    public function setStoreId($store_id);
    /**
     * Get page
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Set setCouponCode
     *
     * @param string $coupon_code coupon_code
     *
     * @return string
     */
    public function setCouponCode($coupon_code);
    /**
     * Get setType
     *
     * @return string|null
     */
    public function getCouponCode();
}
