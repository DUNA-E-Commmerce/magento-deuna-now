<?php


namespace Deuna\Now\Api;
/**
 * CheckoutInterface
 *
 * @category Interface
 * @package  CheckoutInterface
 */
interface CheckoutInterface
{
    /**
     * @param int $cartId
     * @return array|\Magento\Framework\Controller\Result\Json
     * @throws NoSuchEntityException
     */
    public function applycoupon(int $cartId);

    /**
     * @param int $cartId
     * @param string $couponCode
     * @return array|\Magento\Framework\Controller\Result\Json
     * @throws NoSuchEntityException
     */
    public function removecoupon(int $cartId, string $couponCode);
}
