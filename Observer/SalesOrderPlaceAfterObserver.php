<?php
namespace Reach\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;
    
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Reach\Payment\Model\ContractFactory $openContractFactory,
        \Reach\Payment\Helper\Data $reachHelper
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->openContractFactory = $openContractFactory;
        $this->reachHelper = $reachHelper;
        $this->_date = $date;
    }

    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getOrder();
            $payment = $order->getPayment();
            $info = $payment->getMethodInstance()->getInfoInstance();
            $contractId = $info->getAdditionalInformation('contract_id');
            $ocSelected = $info->getAdditionalInformation('oc_selected');
            if ($contractId && $contractId!= '' && $ocSelected) {
                $this->fetchAndSaveContractDetail($order, $contractId);
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/oc-save-error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
        return $this;
    }

    /**
     * Fetch and save contract detail
     *
     * @param \Magento\Sales\Model\Order $order
     * @return true
     */
    protected function fetchAndSaveContractDetail($order, $contractId)
    {
        $detail = $this->fetchContractDetail($contractId);
        if ($detail) {
            $customerId = $order->getCustomerId();
            $contract = $this->openContractFactory->create();
            $contract->setCustomerId($customerId);
            $contract->setReachContractId($contractId);
            $contract->setMethod($detail['method']);
            $contract->setIdentifier($detail['identifier']);
            $contract->setCurrency($detail['currency']);

            if (isset($detail['expire_at'])) {
                $contract->setExpireAt($detail['expire_at']);
            }
            $contract->save();
        }
    }

    /**
     * Fetch contract detail
     *
     * @param string $contractId
     * @return array|null
     */
    protected function fetchContractDetail($contractId)
    {
        $request=[];
        $request['ContractId']=$contractId;
        $request['MerchantId']=$this->reachHelper->getMerchantId();
        $url = $this->reachHelper->getQueryUrl();
        $response = $this->callCurl($url, $request);

        if (isset($response['response']) && isset($response['signature'])) {

            if ($this->validateResponse($response['response'], $response['signature'])) {
                //lack of this line was the problem; without this it was looking for desired elements at wrong place/depth
                //in the nested data structure.
                //Also converting string in $response['response'] to array so that the rest of the code would
                //work with minimal changes.
                $response_extracted = json_decode($response['response'], true);

                if ($response_extracted  && isset($response_extracted['Payment'])) {
                    $detail=[];
                    $detail['currency']= $response_extracted ['ConsumerCurrency'];
                    $detail['method']=$response_extracted ['Payment']['Method'];
                    $detail['identifier']=$response_extracted ['Payment']['AccountIdentifier'];

                    if (isset($response_extracted ['Times']['Expiry'])) {
                        $expredAt = explode('T',$response_extracted['Times']['Expiry']);
                        $detail['expire_at']= $expredAt[0];
                        $time = explode('.', $expredAt[1]);
                        $detail['expire_at'] .= ' '. $time[0];
                    }
                    return $detail;
                }
            }
        }
        return null;
    }

    /**
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
        $json = json_encode($params);
        $secret = $this->reachHelper->getSecret();
        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => 'request='.urlencode($json).'&signature='.urlencode($signature),
        ]);
        $responseString = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $responseString = urldecode($responseString);
        $response =[];
        parse_str($responseString, $response);
        return $response;
    }
}
