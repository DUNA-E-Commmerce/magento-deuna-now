<?php

namespace Deuna\Now\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Adapter\Curl;
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;
use Deuna\Now\Helper\Data;
use Laminas\Http\Request;

class RequestHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const URL_PRODUCTION = 'https://apigw.getduna.com';
    const URL_STAGING = 'https://api.stg.deuna.io';
    const URL_DEVELOPMENT = 'https://api.dev.deuna.io';
    const CONTENT_TYPE = 'application/json';
    const PRIVATE_KEY_PRODUCTION = 'private_key_prod';
    const PRIVATE_KEY_STAGING = 'public_key_sandbox';
    const LOGTAIL_SOURCE = 'magento-bedbath-mx';
    const LOGTAIL_SOURCE_TOKEN = 'DB8ad3bQCZPAshmAEkj9hVLM';
    const DEV_PRIVATE_KEY = 'd09ae647fceb2a30e6fb091e512e7443b092763a13f17ed15e150dc362586afd92571485c24f77a4a3121bc116d8083734e27079a25dc44493496198b84f';

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Json $json,
        Curl $curl,
        Data $helper,
        EncryptorInterface $encryptor,
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->json = $json;
        $this->logger = new Logger(self::LOGTAIL_SOURCE);
        $this->logger->pushHandler(new LogtailHandler(self::LOGTAIL_SOURCE_TOKEN));
    }

    /**
     * @param $body
     * @return mixed
     * @throws LocalizedException
     */
    public function request($endpoint, $method = 'GET', $body = null, $headers = [])
    {
        try {
            switch ($method) {
                case 'POST':
                    $method = Request::METHOD_POST;
                    break;
                case 'PUT':
                    $method = Request::METHOD_PUT;
                    break;
                case 'DELETE':
                    $method = Request::METHOD_DELETE;
                    break;
                case 'HEAD':
                    $method = Request::METHOD_HEAD;
                    break;
                case 'OPTIONS':
                    $method = Request::METHOD_OPTIONS;
                    break;
                default:
                    $method = Request::METHOD_GET;
                    break;
            }

            $url = $this->getUrl() . $endpoint;
            $http_ver = '1.1';
            $headers = $this->getHeaders();

            if ($this->getEnvironment() !== 'prod') {
                $this->logger->debug("Environment", [
                    'method' => $method,
                    'environment' => $this->getEnvironment(),
                    'apikey' => $this->getPrivateKey(),
                    'request' => $url,
                    'body' => $body,
                ]);
            }

            $configuration['header'] = $headers;

            if ($this->getEnvironment() !== 'prod') {
                $this->logger->debug('CURL Configuration sent', [
                    'config' => $configuration,
                ]);
            }

            $this->curl->setConfig($configuration);
            $this->curl->write($method, $url, $http_ver, $headers, $body);

            $response = $this->curl->read();

            $this->logger->debug('CURL Response', [
                'response' => [
                    'body' => $response,
                ],
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->critical('Error on request cancellation', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ]);
        }
    }

    /**
     * @return string
     */
    private function getUrl(): string
    {
        $env = $this->getEnvironment();

        switch ($env) {
            case 'develop':
                return self::URL_DEVELOPMENT;
                break;
            case 'staging':
                return self::URL_STAGING;
                break;
            default:
                return self::URL_PRODUCTION;
                break;
        }
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        return [
            'X-Api-Key: ' . $this->getPrivateKey(),
            'Content-Type: ' . self::CONTENT_TYPE
        ];
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        $env = $this->getEnvironment();

        /**
         * Merchant Dev: MAGENTO
         * Used for local development
         */
        $devPrivateKey = self::DEV_PRIVATE_KEY;

        if ($env == 'develop') {
            return $devPrivateKey;
        } else if ($env == 'staging') {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_STAGING);
        } else {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_PRODUCTION);
        }

        return $this->encryptor->decrypt($privateKey);
    }

    public function getEnvironment()
    {
        return $this->helper->getEnv();
    }
}
