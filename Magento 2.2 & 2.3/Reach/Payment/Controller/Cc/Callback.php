<?php

namespace Reach\Payment\Controller\Cc;

use Magento\Sales\Api\OrderRepositoryInterface;

class Callback extends \Magento\Framework\App\Action\Action
{


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
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->reachHelper = $reachHelper;
        $this->_orderFactory       = $orderFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();    
        try {
            if (isset($params['request']) && isset($params['signature']) && $this->validate($params['request'], $params['signature'])) {
                $response = json_decode($params['request'], true);
                if (isset($response['OrderState']) && $response['OrderState'] == "Processed" && $response['UnderReview'] === false && !count($response['Refunds'])) {
                    $order = $this->loadOrder($response['ReferenceId']);
                    $order = $this->orderRepository->get($order->getId());
                    $order->getPayment()->accept();
                    $this->orderRepository->save($order);
                    $order->getInvoiceCollection()
                    ->setDataToAll('transaction_id', $response['OrderId'])
                    ->save();
                }
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cc-callback-error.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
    }

    private function validate($request, $nonce)
    {
        $key = $this->reachHelper->getSecret();
        $signature =  base64_encode(hash_hmac('sha256', $request, $key, true));
        return $signature == $nonce;
    }

    private function loadOrder($incrementId)
    {
        $order = $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        
        if ($order === null || $order->getId() === null) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Invalid order."));
        }
        return $order;
    }
}
