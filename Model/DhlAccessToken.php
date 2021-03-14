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
    }

    /**
     * Retrieve DHL API access token
     *
     * @return string
     */
    protected function getAccessToken() {

        if ( $this->isTokenValid() )
            return $this->getCachedToken();

        $url = $this->baseUrl;
        $basic = base64_encode($this->clientId.':'.$this->clientSecret);
        $url .= 'account/v1/auth/accesstoken';

        $rest = $this->httpRestFactory->create();
        $rest->setBasicAuth($basic);
        $rest->setUrl($url);

        $response = $rest->executeGet();
        $result = $response->getResponseData();

        if (isset($result['access_token'])) {
            if(isset($result['expires_in'])) {
                $this->setTokenExpiry($result['expires_in']);
                $this->setCachedToken($result['access_token']);
                return $this->getCachedToken();
            }
        }

        return null;
    }

    /**
     * Tests for valid, active access token
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
            $this->_logger->debug("No DHL access token or expired");
        }

        return $result;
    }

    /**
     * Get access token expiry date/time
     *
     * @return DateTime
     */
    public function getTokenExpiry() {
        return $this->session->getTokenExpires();
    }

    /**
     * Sets/overrides the default access token expiry
     *
     * @param int $secondsFromNow
     *
     */
    public function setTokenExpiry($secondsFromNow) {

        $seconds = intval($secondsFromNow);
        if ( $seconds > 0 ) {
            $tokenExpiry = new DateTime('NOW');
            $tokenExpiry->modify("+ {$seconds} seconds");
            $this->session->setTokenExpires($tokenExpiry);
            $this->_logger->debug("DHL access token expiry {$seconds} s.  expires @ {$this->session->getTokenExpires()->format('Y-m-d H:i:s')} UTC");
        }
        else {
            $this->setCachedToken(null);
        }
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
            $this->session->unsTokenExpires();
        }
        else {
            $this->session->setCachedAccessToken($token);
        }

        $this->_logger->debug("new access token {$this->getCachedToken()}");
    }
}
