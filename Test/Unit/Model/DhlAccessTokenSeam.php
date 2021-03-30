<?php

namespace Reach\Payment\Test\Unit\Model;

use Reach\Payment\Model\DhlAccessToken;
use Reach\Payment\Model\Reach;
use \DateTime;

/**
 * Override unit test seam for DhlAccessToken class
 */
class DhlAccessTokenSeam extends DhlAccessToken
{
    /**
     * @var array
     */
    private $apiResponse;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $localAccessToken;

    /**
     * @var DateTime
     */
    private $tokenExpiry;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $baseUrl,
        \Magento\Checkout\Model\Session $session,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $clientId,
            $clientSecret,
            $baseUrl,
            $session,
            $httpRestFactory,
            $logger);
    }

    /**
     * @param int $statusCode
     * @param array $array
     */
    public function SetSimulatedApiResponse($statusCode, $array) {
        $this->apiResponse = $array;
        $this->statusCode = $statusCode;
    }

    protected function callDHLGetAccessTokenApi() {
        $response = $this->apiResponse;
        $response['status_code'] = $this->statusCode;

        return $response;
    }

    protected function getCachedTokenExpiry() {
        return $this->tokenExpiry;
    }

    protected function setCachedTokenExpiry($tokenExpiry) {
        $this->tokenExpiry = $tokenExpiry;
    }

    protected function getCachedToken() {
        return $this->localAccessToken;
    }

    protected function setCachedToken($token) {
        $this->localAccessToken = $token;
    }
}
