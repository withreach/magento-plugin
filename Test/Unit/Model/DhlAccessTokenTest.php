<?php

namespace Reach\Payment\Test\Unit\Model;

use Reach\Payment\Test\Unit\Model\DhlAccessTokenSeam;
use Magento\Checkout\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Reach\Payment\Helper\Data;
use Reach\Payment\Model\Api\HttpRestFactory;
use Reach\Payment\Model\Currency;
use Reach\Payment\Model\DhlAccessToken;
use Reach\Payment\Model\Reach;
use \DateTime;

/**
 * DhlAccessToken class test suite
 */
final class DhlAccessTokenTest extends TestCase
{
    /**
     * @var DhlAccessTokenSeam
     */
    private $testSeam;

    /**
     * @var Session MockObject
     */
    protected $checkoutSession;

    /**
     * @var HttpRestFactory MockObject
     */
    protected $httpRestFactory;

    /**
     * @var \Psr\Log\LoggerInterface MockObject
     */
    protected $logger;

    protected function setUp(): void {
        $this->httpRestFactory = $this->getMockBuilder('Reach\Payment\Model\Api\HttpRestFactory')
            ->disableOriginalConstructor()->setMethods(['create'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock('\Psr\Log\LoggerInterface');
    }

    protected function tearDown(): void {
        unset($this->httpRestFactory);
        unset($this->checkoutSession);
        unset($this->logger);
    }

    public function testGetAccessTokenAPISuccess() {

        $testSeam = new DhlAccessTokenSeam(
            'testClientId',
            'testClientSecret',
            'testUrl',
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger);

        $apiResponse = array(
            "access_token" => "M95LT8xTcyfmjGnjt10AWr40T2in",
            "client_id" => "7NhnAoNOXHryy2F6uGHMjrfyReRyUtUQ",
            "token_type" => "Bearer",
            "expires_in" => 3600
        );

        $testSeam->SetSimulatedApiResponse(200, $apiResponse);

        $this->assertEquals(false, $testSeam->isTokenValid());

        $actualResult = $testSeam->getAccessToken();

        $this->assertEquals(true, $testSeam->isTokenValid());
        $this->assertEquals($apiResponse['access_token'], $actualResult['access_token'] );
        $this->assertEquals(false, isset($actualResult['status_code'] ));

    }

    public function testGetAccessTokenAPI404Failure() {

        $testSeam = new DhlAccessTokenSeam(
            'testClientId',
            'testClientSecret',
            'testUrl',
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger);

        $apiResponse = array();

        $testSeam->SetSimulatedApiResponse(404, $apiResponse);

        $actualResult = $testSeam->getAccessToken();

        $this->assertEquals(true, isset($actualResult['status_code'] ));
        $this->assertEquals(404, $actualResult['status_code'] );
        $this->assertEquals(false, $testSeam->isTokenValid());
    }

    public function testGetAccessTokenAPIAuthFailure() {

        $testSeam = new DhlAccessTokenSeam(
            'badClientId',
            'badClientSecret',
            'testUrl',
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger);

        $apiResponse = array(
            "type" => "https://api-sandbox.dhlecs.com/docs/errors/401.0000007",
            "title" => "Invalid credentials"
        );

        $testSeam->SetSimulatedApiResponse(401, $apiResponse);

        $actualResult = $testSeam->getAccessToken();

        $this->assertEquals(true, isset($actualResult['status_code'] ));
        $this->assertEquals(401, $actualResult['status_code'] );
        $this->assertEquals(false, $testSeam->isTokenValid());
    }

    public function testGetAccessTokenAPISetExpiry() {

        $testSeam = new DhlAccessTokenSeam(
            'testClientId',
            'testClientSecret',
            'testUrl',
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger);

        $apiResponse = array(
            "access_token" => "M95LT8xTcyfmjGnjt10AWr40T2in",
            "client_id" => "7NhnAoNOXHryy2F6uGHMjrfyReRyUtUQ",
            "token_type" => "Bearer",
            "expires_in" => 3600
        );

        $testSeam->SetSimulatedApiResponse(200, $apiResponse);

        $expectedExpiry = new DateTime("now");
        $expectedExpiry->modify("+ {$apiResponse['expires_in']} seconds");

        $testSeam->getAccessToken();
        $this->assertEquals(true, $testSeam->isTokenValid());

        $actualExpiry = $testSeam->getTokenExpiry();

        // compare actual to expected token expiry
        $interval = $actualExpiry->diff($expectedExpiry);
        $deltaTime = intval($interval->format('%s'));

        // accomodate any clock delays in running this test.
        // expected value is token expiry within 5 seconds of when
        // the 'simulated' DHL API set it's expiry
        $this->assertTrue($deltaTime < 5);

    }

    public function testGetAccessTokenAPIExpiredToken() {

        $testSeam = new DhlAccessTokenSeam(
            'testClientId',
            'testClientSecret',
            'testUrl',
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger);

        $apiResponse = array(
            "access_token" => "M95LT8xTcyfmjGnjt10AWr40T2in",
            "client_id" => "7NhnAoNOXHryy2F6uGHMjrfyReRyUtUQ",
            "token_type" => "Bearer",
            "expires_in" => 3
        );

        $testSeam->SetSimulatedApiResponse(200, $apiResponse);

        $actualResult = $testSeam->getAccessToken();
        $this->assertEquals(true, $testSeam->isTokenValid());
        $this->assertEquals($apiResponse['access_token'], $actualResult['access_token'] );

        // wait for token to expire
        sleep( 5 );

        // token should have expired - is no longer valid
        $this->assertEquals(false, $testSeam->isTokenValid());

        $apiResponse = array(
            "access_token" => "xxxLT8xTcyfmjGnjt10AWr40T2in",
            "client_id" => "7NhnAoNOXHryy2F6uGHMjrfyReRyUtUQ",
            "token_type" => "Bearer",
            "expires_in" => 3600
        );

        $testSeam->SetSimulatedApiResponse(200, $apiResponse);

        // get new token
        $actualResult = $testSeam->getAccessToken();

        // ensure new token now in play
        $this->assertEquals(true, $testSeam->isTokenValid());
        $this->assertEquals($apiResponse['access_token'], $actualResult['access_token'] );

    }
}
