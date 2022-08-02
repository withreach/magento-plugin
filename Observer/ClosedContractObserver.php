<?php
namespace Reach\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Reach\Payment\Api\ContractRepositoryInterface;

class ClosedContractObserver implements ObserverInterface
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Reach\Payment\Model\Api\HttpTextFactory
     */
    private $httpTextFactory;


    /** @var LoggerInterface */
    private $_logger;

    /** @var ContractRepositoryInterface $contractRepository */
    private $contractRepository;

    /**
     * @param \Reach\Payment\Helper\Data $reachHelper
     * @param \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory
     * @param \Magento\Framework\Model\Context $context
     * @param ContractRepositoryInterface $contractRepository
     */
    public function __construct(
        \Reach\Payment\Helper\Data $reachHelper,
        \Reach\Payment\Model\Api\HttpTextFactory $httpTextFactory,
        \Magento\Framework\Model\Context $context,
        ContractRepositoryInterface $contractRepository)
    {
        $this->reachHelper = $reachHelper;
        $this->httpTextFactory = $httpTextFactory;
        $this->_logger = $context->getLogger();
        $this->contractRepository = $contractRepository;
    }

    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $expiredContractId = $observer->getData('closed_contract_id');

        $contract = $this->contractRepository->getById($expiredContractId);

        // Only attempt to set the closed at if it's not already set
        if (is_null($contract->getClosedAt())) {
            $detail = $this->fetchContractDetail($expiredContractId);
            $contract->setClosedAt($detail['closed_at']);
            $this->contractRepository->save($contract);
        }
    }

    /**
     * TODO:: Move this method into some kind of helper class since it's essentially
     *  a duplicate of the fetchContractDetail in SalesOrderPlaceAfterObserver
     *
     * Fetch contract detail
     *
     * @param string $contractId
     * @return array|null
     */
    protected function fetchContractDetail($contractId)
    {
        $request=[];
        $request['ContractId'] = $contractId;
        $request['MerchantId'] = $this->reachHelper->getMerchantId();
        $url = $this->reachHelper->getQueryUrl();
        $response = $this->callCurl($url, $request);

        if (isset($response['response']) && isset($response['signature'])) {

            if ($this->validateResponse($response['response'], $response['signature'])) {
                $response_extracted = json_decode($response['response'], true);

                if ($response_extracted  && isset($response_extracted['Payment'])) {
                    $detail=[];
                    $detail['currency']= $response_extracted['ConsumerCurrency'];
                    $detail['method']=$response_extracted['Payment']['Method'];
                    $detail['identifier']=$response_extracted['Payment']['AccountIdentifier'];

                    if (isset($response_extracted['Times']['Expiry'])) {
                        $expiresAt = explode('T',$response_extracted['Times']['Expiry']);
                        $detail['expire_at']= $expiresAt[0];
                        $time = explode('.', $expiresAt[1]);
                        $detail['expire_at'] .= ' '. $time[0];
                    }

                    if (isset($response_extracted['Times']['Closed'])) {
                        $expiresAt = explode('T',$response_extracted['Times']['Closed']);
                        $detail['closed_at']= $expiresAt[0];
                        $time = explode('.', $expiresAt[1]);
                        $detail['closed_at'] .= ' '. $time[0];
                    }
                    return $detail;
                }
            }
        }
        return null;
    }

    /**
     * TODO:: Move this method into some kind of helper class since this method
     *  is duplicated in several classes.
     * validate response
     *
     * @param array $response
     * @param string $nonce
     * @return boolean
     */
    protected function validateResponse($response, $nonce)
    {
        $nonce = str_replace(' ', '+', $nonce);
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $response, $key, true));
        return $signature == $nonce;
    }

    /**
     * TODO:: Move this method into some kind of helper class since this method
     *  is duplicated in several classes.
     * Execute API request
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @return return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function callCurl($url, $params, $method = "POST")
    {
        // Enable lines with logger function to turn on logging for debugging.
        // $this->_logger->debug('---------------- callCurl - START OF REQUEST----------------');

        $json = json_encode($params);
        // $this->_logger->debug('$params: ');
        // $this->_logger->debug(json_encode($params));
        $secret = $this->reachHelper->getSecret();
        // $this->_logger->debug('$secret: ');
        // $this->_logger->debug(json_encode($secret));
        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));
        // $this->_logger->debug('$signature: ');
        // $this->_logger->debug(json_encode($signature));
        $rest = $this->httpTextFactory->create();
        $rest->setContentType("application/x-www-form-urlencoded");
        $rest->setUrl($url);
        // $this->_logger->debug('$url: ');
        // $this->_logger->debug(json_encode($url));
        $result = $rest->executePost('request='.urlencode($json).'&signature='.urlencode($signature));
        $responseString = $result->getResponseData();
        $response =[];
        parse_str($responseString, $response);
        // $this->_logger->debug('---------------- callCurl - END OF REQUEST----------------');
        return $response;
    }
}
