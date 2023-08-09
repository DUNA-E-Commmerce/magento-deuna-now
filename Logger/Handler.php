<?php

namespace Deuna\Now\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/deuna.log';
}
