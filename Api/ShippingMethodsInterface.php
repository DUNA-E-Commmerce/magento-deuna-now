<?php

namespace Deuna\Now\Api;

interface ShippingMethodsInterface
{

    /**
     * @param int $cartId
     * @return mixed
     */
    public function get(int $cartId);

    /**
     * @param int $cartId
     * @param string $code
     * @return mixed
     */
    public function set(int $cartId, string $code);

}
