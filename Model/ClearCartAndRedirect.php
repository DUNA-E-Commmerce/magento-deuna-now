<?php
namespace Deuna\Now\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class ClearCartAndRedirect
{
    protected $checkoutSession;
    protected $messageManager;
    protected $context;

    public function __construct(
        CheckoutSession $checkoutSession,
        ManagerInterface $messageManager,
        Context $context
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->context = $context;
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
            $this->messageManager->addSuccessMessage(__('Tu compra se ha realizado con éxito.'));

            $resultRedirect = $this->context->getResultRedirectFactory()->create();
            $resultRedirect->setPath('checkout/onepage/success'); 

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
