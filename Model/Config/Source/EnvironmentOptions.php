<?php
namespace Deuna\Now\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class EnvironmentOptions implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'production', 'label' => __('Producción')],
            ['value' => 'sandbox', 'label' => __('Sandbox')]
        ];
    }
}
