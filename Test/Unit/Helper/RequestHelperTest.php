<?php

namespace Deuna\Now\Test\Unit\Helper;

use Deuna\Now\Helper\RequestHelper;
use PHPUnit\Framework\TestCase;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Encryption\EncryptorInterface;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;
use Deuna\Now\Helper\Data;

class RequestHelperTest extends TestCase
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    protected function setUp(): void
    {

        $mockedContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedCurl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedEncryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->getMock();

        $this->requestHelper = new RequestHelper(
            $mockedContext,
            $mockedJson,
            $mockedCurl,
            $mockedHelper,
            $mockedEncryptor
        );

    }

    public function testGetEnviroment()
    {

        $result = $this->requestHelper->getEnvironment();
        
        $this->assertEquals('', $result);

    }


    // public function testSuccessfulGetRequest()
    // {
        

    //     // $response = $this->requestHelper->request('/rest/V1/deuna/public-key', 'GET');
        

    //     // $this->assertEquals('Mocked response', $response);
    // }
}
