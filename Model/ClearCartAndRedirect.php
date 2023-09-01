<?php
namespace Deuna\Now\Model;

use Magento\Checkout\Model\Session as CheckoutSession;

class ClearCartAndRedirect
{
    protected $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession,
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Clears the shopping cart.
     *
     * @return bool Success or failure in clearing the cart.
     */
    public function clearCart()
    {
        try {
            $this->checkoutSession->clearQuote();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
