<?php

namespace Reach\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;


class CcHelper extends AbstractHelper
{
    /**
     * @var \Magento\Framework\UrlInterface
     */

    protected $coreUrl;


    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */

    protected $transactionModel;

    /**
     * @param Context $context
     * @param EncryptorInterface $enc
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $coreUrl
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        //\Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel
    ) {
        //$this->_logger =$logger;
        $this->coreUrl = $coreUrl;
        $this->transactionModel = $transactionModel;
        parent::__construct($context);

    }

    /**
     * Build request array
     *
     * @param InfoInterface|Payment|Object  $payment
     * @param float $amount
     * @param Reach\Payment\Helper\Data $reachHelper
     * @param \Magento\Payment\Model\InfoInterface  $info;
     * @param Reach\Payment\Model\Currency $reachCurrency
     * @return array
     */
    public function _buildCheckoutRequest($payment, $amount, $reachHelper, $info, $reachCurrency)
    {
        $request=[];

        //$this->_logger is coming from AbstractHelper
        $this->_logger->debug("payment :::".json_encode($payment));

        $order = $payment->getOrder();

        $this->_logger->debug("order :::".json_encode($order));
        $this->_logger->debug("info :::".json_encode($info));

        $request['MerchantId'] = $reachHelper->getMerchantId();
        $request['ReferenceId'] = $order->getIncrementId();
        $consumer = $this->getConsumerInfo($order);
        $request['Consumer'] = $consumer;
        $request['Notify'] = $this->getCallbackUrl($order);
        $request['ConsumerCurrency']= $order->getOrderCurrencyCode();

        $rateOfferId =   $reachCurrency->getOfferId($order->getOrderCurrencyCode());

        if ($rateOfferId) {
            $request['RateOfferId'] = $rateOfferId;
        }

        $request['DeviceFingerprint'] = $info->getAdditionalInformation("device_fingerprint");
        $contractId = $info->getAdditionalInformation('contract_id');
        if (isset($contractId) && is_string($contractId)) {
            $request['ContractId'] = $contractId;

        } else {
            $stashId = $info->getAdditionalInformation("stash_id");
            $request['StashId'] = $stashId;
            $request['PaymentMethod'] = $this->getMethodName($payment->getCcType());

            $openContract = $info->getAdditionalInformation('oc_selected');
            if ($openContract && $openContract != 0) {
                $request['OpenContract'] = true;
            }
        }
        $request['Items']=[];
        foreach ($order->getAllVisibleItems() as $item) {
            //In case of a configurable product (that is a product with multiple attributes like size, color etc.
            //Magento (by design) inserts more than one rows in the database.
            //In such a case if only one representative row is not used during the checkout as well as reporting/accounting
            // processes then it causes problem by counting a product more than once.
            //getAllVisibleItems() used to help with retrieving only one (representative) row in the past but it no
            //longer works with newer versions of Magento (it is mentioned in a comment here
            //https://stackoverflow.com/questions/7877566/magento-order-getallitems-return-twice-the-same-item).
            //The solution as specified in one of the comments here:
            //https://magento.stackexchange.com/questions/111112/magento2-correct-way-to-get-order-items worked.
            //Basically if a product row does not have a parent then consider it.
            //On the other hand if a product row has a parent then do not consider such a product row.
            //In this case consider the parent item instead.
            //More about configurable products here: https://docs.magento.com/m2/ee/user_guide/catalog/product-types.html
            if ($item->getProductType() == "simple" && ($item->getParentItem())) {
                continue;
            }
            $itemData=[];
            $itemData['Sku'] = $item->getSku();
            $itemData['ConsumerPrice'] =  $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$item->getPrice());
            $itemData['Quantity'] = $item->getQtyOrdered();
            $request['Items'][]=$itemData;
        }
        $request['ShippingRequired'] = false;

        $request['Shipping']=[];
        if ($order->getReachDuty()) {
            $request['ShippingRequired'] = true;
            $request['Shipping']['ConsumerDuty']= $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$order->getReachDuty());
        } else {
            $request['Shipping']['ConsumerDuty']=0;
        }
        $request['Shipping']['ConsumerPrice']= $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$order->getShippingAmount());
        $request['Shipping']['ConsumerTaxes']= $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$order->getTaxAmount());

        $request['Consignee']= $this->getConsigneeInfo($order);
        if ($order->getDiscountAmount()) {
            $request['Discounts']=[];
            $discountAmount =  $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$order->getDiscountAmount() * -1);
            $request['Discounts'][]=['Name'=>$order->getCouponCode()?$order->getCouponCode():'Discount','ConsumerPrice'=>$discountAmount];
        }
        $request['ConsumerTotal']= $reachCurrency->convertCurrency($order->getOrderCurrencyCode(),$order->getGrandTotal());
        $this->_logger->debug("RQUEST :::".json_encode($request));
        return $request;
    }

    /**
     * get consumer info
     *
     * @param object $order
     * @return array
     */
    protected function getConsumerInfo($order)
    {
        $billingAddress = $order->getBillingAddress();
        return [
            'Name' => $order->getCustomerName(),
            'Email' => $order->getCustomerEmail(),
            'Phone' => $billingAddress->getTelephone(),
            'Region' => $billingAddress->getRegionCode(),
            'Address' => implode(" ", $billingAddress->getStreet()),
            'City' => $billingAddress->getCity(),
            'PostalCode' => $billingAddress->getPostcode(),
            'Country' => $billingAddress->getCountryId()
        ];
    }


    /**
     * Get notify url
     *
     * @param object $order
     * @return string
     */
    private function getCallbackUrl($order)
    {
        $url = $this->coreUrl->getUrl('reach/cc/callback', [
            '_secure' => true,
            '_store'  => $order->getStoreId()
        ]);
        $url .= "?orderid=" . $order->getQuoteId();
        $this->_logger->debug("url :::".$url);
        return  $url;
    }


    /**
     * If response is failed throw exception
     *
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function processErrors($response)
    {

        if (isset($response['Error']) && count($response['Error'])) {
            $errorMessage = $response['Error']['Code'];
            if (isset($response['Error']['Message']) && $response['Error']['Message'] != '') {
                $errorMessage = ':'.$response['Error']['Message'];
            }
            throw new \Magento\Framework\Exception\LocalizedException(
                __($errorMessage)
            );
        }
    }

    /**
     * @param DataObject $payment
     * @param DataObject $response
     *
     * @return Object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setTransStatus($payment, $response, $capture = false)
    {

        if (isset($response['OrderId']) && isset($response['Authorized']) && $response['Authorized']===true) {
            $payment->setTransactionId($response['OrderId'])->setIsTransactionClosed(0);
        }

        if ($capture) {
            if ($response['Completed']===false) {
                $payment->setIsTransactionPending(true);
            }
        }
        if (isset($response['ContractId'])) {
            $payment->setAdditionalInformation('contract_id', $response['ContractId']);
        }

        return $payment;
    }

    /**
     * validate response
     *
     * @param array $response
     * @param string $nonce
     * @return boolean
     */
    public function validateResponse($response, $nonce)
    {
        $nonce = str_replace(' ', '+', $nonce);
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $response, $key, true));
        return $signature == $nonce;
    }


    /**
     * get order consignee information
     *
     * @param object $order
     * @return array
     */
    public function getConsigneeInfo($order)
    {
        $shippingAddress = $order->getShippingAddress();
        return [
            'Name' => $order->getCustomerName(),
            'Email' => $order->getCustomerEmail(),
            'Phone' => $shippingAddress->getTelephone(),
            'Region' => $shippingAddress->getRegionCode(),
            'Address' => implode(" ", $shippingAddress->getStreet()),
            'City' => $shippingAddress->getCity(),
            'PostalCode' => $shippingAddress->getPostcode(),
            'Country' => $shippingAddress->getCountryId()
        ];
    }



    public function getReferenceIdForRefund($payment)
    {
        $collection = $this->transactionModel->getCollection();
        $collection->addOrderIdFilter($payment->getOrder()->getId());
        $collection->addTxnTypeFilter(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $collection->setOrder('transaction_id', 'DESC');
        if ($collection->count() && $collection->getFirstItem()->getTxnId()) {
            return $collection->getFirstItem()->getTxnId();
        }
        return $payment->getParentTransactionId();
    }

}
