<?php

namespace Reach\Payment\Model;

use Reach\Payment\Api\Data\ResponseInterface;
use Reach\Payment\Api\NotificationInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * ReachCurrency model
 *
 */
class ReachNotification implements NotificationInterface
{
    /** @var ResponseInterface  */
    protected $response;

    /**
     * @var \Reach\Payment\Helper\Data
     */
    protected $reachHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(
        ResponseInterface $response,
        \Reach\Payment\Helper\Data $reachHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->response = $response;
        $this->reachHelper = $reachHelper;
        $this->_orderFactory       = $orderFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $request
     * @param $signature
     * @return ResponseInterface
     */
    public function handleNotification($request, $signature)
    {
        try {
            if ($this->validate($request, $signature)) {
                // TODO:: Handle other Notification order states
                $response = json_decode($request, true);
                if (isset($response['OrderState']) && $response['OrderState'] == "Processed" && $response['UnderReview'] === false && !count($response['Refunds'])) {
                    $order = $this->loadOrder($response['ReferenceId']);
                    $order = $this->orderRepository->get($order->getId());
                    $order->getPayment()->accept();
                    $this->orderRepository->save($order);
                    $order->getInvoiceCollection()
                        ->setDataToAll('transaction_id', $response['OrderId'])
                        ->save();

                    $this->response->setSuccess(true);
                    $this->response->setResponse('Successfully processed Notification');
                } else {
                    $this->response->setSuccess(true);
                    $this->response->setResponse('Unhandled Notification State');
                }
            }
        } catch (\Exception $e) {
            throw new Exception(new Phrase($e->getMessage()), 400, 404);
        }

        return $this->response;
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
