<?php


namespace Reach\Payment\Controller\Paypal;

class Processing extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {

        
        parent::__construct($context);
        $this->_checkoutSession    = $checkoutSession;
        $this->_quote              = $quote;
        $this->orderEmailSender    = $orderEmailSender;
        $this->_orderFactory       = $orderFactory;
        $this->_quoteFactory       = $quoteFactory;
        $this->transactionFactory  = $transactionFactory;
        $this->_quoteRepository    = $quoteRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            $response = json_decode($params['response'], true);
            $this->loadQuote($params['quoteid']);
            $order = $this->loadOrder();
            $payment = $order->getPayment();

            $this->validateResponse($response);
           
            $this->setTransactionData($response['OrderId'], $payment, $response['OrderState']);
            $methodInstance = $payment->getMethodInstance();
            $methodInstance->markAsInitialized();
            $order->place()->save();
            $this->orderEmailSender->send($order);

            $closed = 0;
            if ($payment->getMethodInstance()->getConfigPaymentAction()=='authorize' && $response['OrderState'] == "PaymentAuthorized") {
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
            }
            elseif ( $response['OrderState'] == "Processed" && $response['Captured']) {
                $closed = 1;
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
            }

            $transaction = $this->transactionFactory->create();
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($response['OrderId']);
            $transaction->setOrderId($order->getEntityId());
            $transaction->setTxnType($action);
            $transaction->setPaymentId($payment->getId());
            $transaction->setIsClosed($closed);
            $transaction->save();

            $order->getInvoiceCollection()
            ->setDataToAll('transaction_id', $payment->getLastTransId())
            ->save();

            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());
            $this->_checkoutSession->setData("reach_order_pending_payment", null);

            $this->_redirect('checkout/onepage/success');


            return;
        } catch (\Exception $e) {
            $this->messageManager->addError('We can\'t place the order: ' . $e->getMessage());

            $this->_quoteRepository->save($this->_quote); // Needs to be added as part of the constructor

            // TODO:: Determine which of these are actually needed
            $this->_checkoutSession->clearHelperData(); // Clear misc checkout parameters
            $this->_checkoutSession->replaceQuote($this->_quote); // Sets the quote for the current session
            $this->_checkoutSession->setData("reach_order_pending_payment", null); // Clears the order id from this key

            $this->_redirect('checkout/cart');
        }
    }
    
    private function validateResponse($response)
    {
        if (empty($response) || !isset($response['OrderState']) ||!isset($response['OrderState'])) {
            if (!empty($response) && isset($response['Error'])) {
                $message = isset($response['Error']['Code'])?$response['Error']['Code']:"";
                $message .= isset($response['Error']['Message'])?$response['Error']['Message']:"";
                throw new \Magento\Framework\Exception\LocalizedException(__($message));
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("Can not place PayPal order, please try another payment method"));
            }
        }
    }

    private function loadQuote($quoteId)
    {
        $this->_quote = $this->_quoteFactory->create()->load($quoteId);
        if (empty($this->_quote->getId())) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Unable to find payment data."));
        }
    }

    private function loadOrder()
    {
        $order = $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
        if ($order === null || $order->getId() === null) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Invalid order."));
        }
        return $order;
    }

    /**
     * @param $transactionId
     * @param $payment
     * @throws \Magento\Framework\Validator\Exception
     */
    private function setTransactionData($transactionId, $payment, $orderState)
    {
        $transactionId = str_replace('-', '', $transactionId);
        if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) {
            $payment->setLastTransId($transactionId);
            $payment->setAdditionalInformation('orderState', $orderState);
            $payment->save();
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id'));
        }
    }
}
