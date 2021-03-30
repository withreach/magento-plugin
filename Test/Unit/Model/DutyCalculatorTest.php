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
 * DutyCalculator class test suite
 */
class DutyCalculatorTest
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory
     */
    protected $csvHsCodeFactory;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $regionModel;

    /**
     * @var \Reach\Payment\Model\Api\HttpRestFactory
     */
    protected $httpRestFactory;

    /**
     * @var \Reach\Payment\Api\Data\DutyResponseInterface
     */
    protected  $response;

    /**
     * @var \Psr\Log\LoggerInterface
     */
     protected $logger;

    protected function setUp(): void {

        $this->reachHelper = $this->createMock('\Reach\Payment\Helper\Data');
        $this->quoteRepository = $this->createMock('\Magento\Quote\Api\CartRepositoryInterface');
        $this->quoteIdMaskFactory = $this->createMock('\Magento\Quote\Model\QuoteIdMaskFactory');
        $this->scopeConfig = $this->createMock('ScopeConfigInterface');
        $this->storeManager = $this->createMock('StoreManagerInterface');
        $this->priceCurrency = $this->createMock('PriceCurrencyInterface');
        $this->csvHsCodeFactory = $this->createMock('\Reach\Payment\Model\ResourceModel\CsvHsCodeFactory');
        $this->regionModel = $this->createMock('\Magento\Directory\Model\Region');
        $this->response = $this->createMock('\Reach\Payment\Api\Data\DutyResponseInterface');
        $this->logger = $this->createMock('\Psr\Log\LoggerInterface');

        $this->httpRestFactory = $this->getMockBuilder('Reach\Payment\Model\Api\HttpRestFactory')
            ->disableOriginalConstructor()->setMethods(['create'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder('\Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void {
        unset($this->reachHelper);
        unset($this->quoteRepository);
        unset($this->quoteIdMaskFactory);
        unset($this->scopeConfig);
        unset($this->storeManager);
        unset($this->priceCurrency);
        unset($this->csvHsCodeFactory);
        unset($this->regionModel);
        unset($this->response);
        unset($this->httpRestFactory);
        unset($this->checkoutSession);
        unset($this->logger);
    }

    public function testGetDutyTaxAPISuccess() {

        $testSeam = new DutyCalculatorSeam(
            $this->reachHelper,
            $this->checkoutSession,
            $this->quoteRepository,
            $this->quoteIdMaskFactory,
            $this->scopeConfig,
            $this->storeManager,
            $this->priceCurrency,
            $this->csvHsCodeFactory,
            $this->regionModel,
            $this->httpRestFactory,
            $this->response,
            $this->logger);

        $json = '{
            "quoteId": "987dc9a0-fc4e-4612-ab0c-de346fd68f3c",
            "pickupAccount": "0005351244",
            "itemSeller": "reach-test-002.myshopify.com",
            "pricingStrategy": "AVERAGE",
            "feeTotals": [
                {
                    "name": "DUTY",
                    "currency": "USD",
                    "value": 0.00
                },
                {
                    "name": "EXCISE",
                    "currency": "USD",
                    "value": 0.00
                },
                {
                    "name": "GST",
                    "currency": "USD",
                    "value": 30.10
                }
            ],
            "consigneeAddress": {
                "state": "SG",
                "country": "SG"
            },
            "packageDetails": {
                "freightCharge": {
                    "value": 10.00,
                    "currency": "USD"
                },
                "insuranceCharge": {
                    "value": 20.00,
                    "currency": "USD"
                },
                "outputCurrency": "USD",
                "clearanceMode": "COURIER",
                "transportMode": "AIR",
                "endUse": "PERSONAL",
                "packageFees": []
            },
            "customsDetails": [
                {
                    "itemId": "29616088973377",
                    "hsCode": "12.01.10",
                    "hsCodeApplied": "120110",
                    "skuNumber": "ABCDEFGHIJKLMNOPQRSTUVW",
                    "productIdentifiers": {
                        "upc": "123456789101",
                        "ean": "88888888",
                        "jan": "1234567890123",
                        "isbn": "1234567890",
                        "mpn": "ABCDEFGH",
                        "brand": "ABCDEFGHIJKLMNOPQRS"
                    },
                    "productCategory": "ABCDEFGHIJ",
                    "itemShortDescription": "ABCDEFGHIJKLMNOPQRS",
                    "itemDescription": "ABCDEFGHIJKL",
                    "size": "ABCD",
                    "gender": "FEMALE",
                    "ageGroup": "TODDLER",
                    "color": "ABCD",
                    "style": "ABCDEFGHIJKLMN",
                    "composition": [
                        "COTTON",
                        "POLYESTER"
                    ],
                    "condition": "NEW",
                    "countryOfOrigin": "CN",
                    "qualifiesForPreferentialTariffs": false,
                    "itemQuantity": {
                        "value": 2,
                        "unit": "EA"
                    },
                    "itemValue": {
                        "value": 200.00,
                        "currency": "USD"
                    },
                    "itemFreight": {
                        "value": 5.00,
                        "currency": "USD"
                    },
                    "itemInsurance": {
                        "value": 10.00,
                        "currency": "USD"
                    },
                    "width": {
                        "value": 20,
                        "unit": "CM"
                    },
                    "length": {
                        "value": 20,
                        "unit": "CM"
                    },
                    "height": {
                        "value": 20.50,
                        "unit": "CM"
                    },
                    "weight": {
                        "value": 5,
                        "unit": "KG"
                    },
                    "volume": {
                        "value": 776,
                        "unit": "CM3"
                    },
                    "area": {
                        "value": 776,
                        "unit": "M2"
                    },
                    "itemFees": [
                        {
                            "name": "DUTY",
                            "currency": "USD",
                            "value": 0.00
                        },
                        {
                            "name": "GST",
                            "currency": "USD",
                            "value": 30.10
                        },
                        {
                            "name": "EXCISE",
                            "currency": "USD",
                            "value": 0.00
                        }
                    ]
                }
            ],
            "senderAddress": {
                "state": "GA",
                "country": "US"
            }
        }';

        $apiResponse = json_decode($json);

        $testSeam->SetSimulatedQuoteApiResponse(401, $apiResponse);

        $testSeam->callDHLDutyTaxApi()

        $this->assertEquals(true, isset($actualResult['status_code'] ));
        $this->assertEquals(401, $actualResult['status_code'] );
        $this->assertEquals(false, $testSeam->isTokenValid());
    }
}
