<?php

namespace Deuna\Now\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Deuna\Now\Helper\LogtailHelper as Logger;
use Deuna\Now\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class RequestHelper extends AbstractHelper
{
    const URL_PRODUCTION = 'https://apigw.getduna.com';
    const URL_STAGING = 'https://api.stg.deuna.io';
    const URL_DEVELOPMENT = 'https://api.dev.deuna.io';
    const CONTENT_TYPE = 'application/json';
    const PRIVATE_KEY_PRODUCTION = 'private_key_prod';
    const PRIVATE_KEY_STAGING = 'private_key_sandbox';
    const DEV_PRIVATE_KEY = 'd09ae647fceb2a30e6fb091e512e7443b092763a13f17ed15e150dc362586afd92571485c24f77a4a3121bc116d8083734e27079a25dc44493496198b84f';

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

    /**
     * @var Http
     */
    protected $request;

    public function __construct(
        Context $context,
        Curl $curl,
        Data $helper,
        Logger $logger,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
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
                    $method = Http::METHOD_POST; // Reemplazo de Request::METHOD_POST
                    break;
                case 'PUT':
                    $method = Http::METHOD_PUT; // Reemplazo de Request::METHOD_PUT
                    break;
                case 'DELETE':
                    $method = Http::METHOD_DELETE; // Reemplazo de Request::METHOD_DELETE
                    break;
                case 'HEAD':
                    $method = Http::METHOD_HEAD; // Reemplazo de Request::METHOD_HEAD
                    break;
                case 'OPTIONS':
                    $method = Http::METHOD_OPTIONS; // Reemplazo de Request::METHOD_OPTIONS
                    break;
                default:
                    $method = Http::METHOD_GET; // Reemplazo de Request::METHOD_GET
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

        $devPrivateKey = self::DEV_PRIVATE_KEY;

        if ($env == 'develop') {
            return $devPrivateKey;
        } else if ($env == 'staging') {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_STAGING);
        } else {
            $privateKey = $this->helper->getGeneralConfig(self::PRIVATE_KEY_PRODUCTION);
        }

        return $privateKey;
    }

    public function getEnvironment()
    {
        return $this->helper->getEnv();
    }
}