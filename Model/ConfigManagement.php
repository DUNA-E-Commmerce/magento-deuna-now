<?php

namespace Deuna\Now\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigManagement {

    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Public Key
     *
     * @return array
     */
    public function getPublicKey()
    {
        $enviroment = $this->scopeConfig->getValue('payment/deuna/environment');
        
        if ($enviroment === 'production'){
            return $this->scopeConfig->getValue('payment/deuna/public_key_prod');
        }else{
            return $this->scopeConfig->getValue('payment/deuna/public_key_sandbox');
        }
    }
}
