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
     * Limpia el carrito de compras.
     *
     * @return bool Éxito o fallo al limpiar el carrito.
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
