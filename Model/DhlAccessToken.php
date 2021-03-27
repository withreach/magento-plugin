<?php

namespace Reach\Payment\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use \DateTime;

class DhlAccessToken implements \Reach\Payment\Api\RestAccessTokenInterface
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    /**
     * @var \Reach\Payment\Model\Api\HttpRestFactory
     */
    private $httpRestFactory;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $baseUrl
     * @param \Magento\Checkout\Model\Session $session
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $baseUrl,
        \Magento\Checkout\Model\Session $session,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $baseUrl;
        $this->session = $session;
        $this->httpRestFactory = $httpRestFactory;
        $this->logger = $logger;

        $sanitizedClientId = substr($this->clientId, -2);
        $sanitizedClientSecret = substr($this->clientSecret, -2);

        $this->logger->debug("access token clientId *****{$sanitizedClientId}");
        $this->logger->debug("access token clientSecret *****{$sanitizedClientSecret}");
        $this->logger->debug("access token baseUrl {$this->baseUrl}");
    }

    /**
     * Retrieve DHL API access token
     *
     * @return array
     */
    public function getAccessToken() {

        $response = [];

        if ( $this->isTokenValid() ) {
            $response['access_token'] = $this->getCachedToken();
            $response['status_code'] = 200;
        }
        else {

            $result = $this->callDHLGetAccessTokenApi();

            if (($result['status_code'] == 200) && isset($result['access_token'])) {
                if(isset($result['expires_in'])) {
                    $this->setTokenExpiry($result['expires_in']);
                    $this->setCachedToken($result['access_token']);
                    $response['access_token'] = $this->getCachedToken();
                }
            }
            else {
                $error = json_encode($result);
                $this->logger->error("DHL API call returned error {$error}");
                $this->setCachedToken(null);
            }

            $response['status_code'] = $result['status_code'];

        }

        $this->logger->debug("DHL access token call: " . json_encode($response));

        return $response;
    }

    /**
     * Call DHL V4 API to get access token
     *
     * @return array json response
     */
    protected function callDHLGetAccessTokenApi() {

        $result = [];
        $url = $this->baseUrl;
        $url .= 'auth/v4/accesstoken';

        $rest = $this->httpRestFactory->create();
        $rest->setUrl($url);
        $rest->setContentType('application/x-www-form-urlencoded');

        $response = $rest->executePost("grant_type=client_credentials&client_id={$this->clientId}&client_secret={$this->clientSecret}");

        $result = $response->getResponseData();
        $result['status_code'] = $response->getStatus();
        $result['url'] = $url;

        return $result;
    }

    /**
     * Tests if have valid, active access token
     *
     * @return bool
     */
    public function isTokenValid() {

        $result = true;

        $token = $this->getCachedToken();
        if ( $token == null ) {
            $result = false;
        }
        else {
            $tokenExpiry = $this->getTokenExpiry();
            if ( $tokenExpiry == null ) {
                $result = false;
            }
            else {
                $now = new DateTime('NOW');
                if ( $now >= $tokenExpiry ) {
                    $result = false;
                }
            }
        }

        if (!$result) {
            $this->logger->debug("No current DHL access token or has expired");
        }

        return $result;
    }

    /**
     * Get access token expiry date/time
     *
     * @return DateTime
     */
    public function getTokenExpiry() {
        return $this->getCachedTokenExpiry();
    }

    /**
     * Sets/overrides the default access token expiry
     *
     * @param int $secondsFromNow (= 0, immediate expiry)
     *
     */
    public function setTokenExpiry($secondsFromNow) {

        $seconds = intval($secondsFromNow);
        if ( $seconds > 0 ) {
            $this->logger->debug("DHL access token expiry in {$secondsFromNow} seconds");
            $tokenExpiry = new DateTime('NOW');
            $tokenExpiry->modify("+ {$seconds} seconds");
            $this->setCachedTokenExpiry($tokenExpiry);
        }
        else {
            $this->setCachedToken(null);
        }
    }

    /**
     * Retrieves access token expiry from session
     *
     * @return DateTime
     */
    protected function getCachedTokenExpiry() {
        return $this->session->getTokenExpiry();
    }

    /**
     * Sets access token expiry in session
     *
     * @param DateTime
     */
    protected function setCachedTokenExpiry($tokenExpiry) {
        $this->session->setTokenExpiry($tokenExpiry);
        $this->logger->debug("DHL access token expiry at {$this->getCachedTokenExpiry()->format('Y-m-d H:i:s')} UTC");
    }

    /**
     * Retrieves access token from session
     *
     * @return string
     */
    protected function getCachedToken() {
        return $this->session->getCachedAccessToken();
    }

    /**
     * Sets the access token in session
     *
     * @param string $token
     */
    protected function setCachedToken($token) {

        if ( $token == null ) {
            $this->session->unsTokenExpiry();
        }
        else {
            $this->session->setCachedAccessToken($token);
        }

        $this->logger->debug("access token {$this->getCachedToken()}");
    }
}
