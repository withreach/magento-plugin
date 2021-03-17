<?php

namespace Reach\Payment\Test\Unit\Model;

use Reach\Payment\Model\DutyCalculator;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Reach\Payment\Model\Reach;
use \DateTime;

/**
 * Override unit test seam for DutyCalculator class
 */
class DutyCalculatorSeam extends DutyCalculator
{
    /**
     * @var array
     */
    private $apiQuoteResponse;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * Constructor
     *
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory $csvHsCodeFactory
     * @param \Magento\Directory\Model\Region $regionModel
     * @param \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory
     * @param \Reach\Payment\Api\Data\DutyResponseInterface $response
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        \Reach\Payment\Model\ResourceModel\CsvHsCodeFactory $csvHsCodeFactory,
        \Magento\Directory\Model\Region $regionModel,
        \Reach\Payment\Model\Api\HttpRestFactory $httpRestFactory,
        \Reach\Payment\Api\Data\DutyResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $reachHelper,
            $checkoutSession,
            $quoteRepository,
            $quoteIdMaskFactory,
            $scopeConfig,
            $storeManager,
            $priceCurrency,
            $csvHsCodeFactory,
            $regionModel,
            $httpRestFactory,
            $response,
            $logger);

        $this->dhlAccessToken = new DhlAccessTokenSeam(
            $this->reachHelper->getDhlApiKey(),
            $this->reachHelper->getDhlApiSecret(),
            $this->reachHelper->getDhlApiUrl(),
            $this->checkoutSession,
            $this->httpRestFactory,
            $this->logger
        );
    }

    /**
     * @param int $statusCode
     * @param array $array
     */
    public function SetSimulatedQuoteApiResponse($statusCode, $array) {
        $this->apiQuoteResponse = $array;
        $this->statusCode = $statusCode;
    }

    /**
     * Call DHL V4 API to get quote
     *
     * @param array $request
     * @param string $accessToken
     * @return array
     */
    protected function getQuote()
    {
        return $this->apiQuoteResponse;
    }
}
