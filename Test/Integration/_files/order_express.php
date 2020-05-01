<?php
/**
 *Adapted one that came with Magento to fit our need
 */

$addressData = include __DIR__ . '/address_data.php';
$billingAddress = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Sales\Model\Order\Address::class,
    ['data' => $addressData]
);
$billingAddress->setAddressType('billing');
$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

$payment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Sales\Model\Order\Payment::class
);

$payment->setMethod(\Magento\Payment\Model\Method\Cc::STATUS_APPROVED); //\Magento\Paypal\Model\Config::METHOD_WPP_EXPRESS);

$amount = 250.10;

/** @var \Magento\Sales\Model\Order $order */
$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Magento\Sales\Model\Order::class);

$order->setCustomerEmail('co1@co1.co')
     ->setIncrementId('100000001') //               //setIncrementId('100000001')
    ->setSubtotal($amount)
    ->setBaseSubtotal($amount)
    ->setBaseGrandTotal($amount)
    ->setGrandTotal($amount)
    ->setBaseCurrencyCode('USD')
    ->setCustomerIsGuest(true)
    ->setStoreId(1)
    ->setEmailSent(true)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setPayment($payment);

$order->getInvoiceCollection()
    ->setDataToAll('transaction_id', $order->getId());


$order->save();

print_r("After save");

var_dump($order->getIncrementId());


var_dump($order->getId());