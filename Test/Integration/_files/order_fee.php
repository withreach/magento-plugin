<?php
/**
 *Adapted one that came with Magento to fit our need
 */

use Magento\Checkout\Model\Session;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Model\Info;
use Magento\Payment\Model\Method\Adapter;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

use Magento\TestFramework\Helper\Bootstrap;

//the data fixtures for customer, ordre, quote

$addressData = include __DIR__ . '/address_data.php';

//If I use \Magento\Sales\Model\Order\Address::class instead of the AddressInterface
//as the data type then it causes problem while using the billing and shipping
// addresses while creating the quote object

$billingAddress = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    Magento\Quote\Api\Data\AddressInterface::class ,
    ['data' => $addressData]
);
$billingAddress->setAddressType('billing');
$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');



/** @var $product \Magento\Catalog\Model\Product */
$product = Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Model\Product::class);
$product
    ->setTypeId('simple')
    ->setId(3)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product 2')
    ->setSku('simple_product_2')  //simple product ; use this sku while testing and assertion
    ->setPrice(10)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->save();



$store = Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Store\Model\StoreManagerInterface::class)
    ->getStore();


/** @var \Magento\Quote\Model\Quote $quote */
$quote = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Quote\Model\Quote::class);
$quote->setCustomerIsGuest(true)
    ->setStoreId($store->getId())
    ->setReservedOrderId('test01')
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->addProduct($product);


$quote->getPayment()->setMethod('checkmo');
$quote->setIsMultiShipping('0');
$quote->collectTotals();

$quoteRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Quote\Api\CartRepositoryInterface::class);
$quoteRepository->save($quote);

/** @var \Magento\Sales\Model\Order $order */
$payment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Magento\Sales\Api\Data\OrderPaymentInterface::class);
$payment->setMethod('checkmo');

$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Magento\Sales\Model\Order::class);
$amount = 100;
$order->setCustomerEmail('co1@co1.co')
    ->setCustomerId($customer->getId())
    ->setIncrementId('100000005')
    ->setSubtotal($amount)
    ->setBaseSubtotal($amount)
    ->setBaseGrandTotal($amount)
    ->setGrandTotal($amount)
    ->setBaseCurrencyCode('USD')
    ->setCustomerIsGuest(true)
    ->setStoreId(1)
    ->setEmailSent(true)
    ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
    ->setQuote($quote)->setPayment($payment);

//setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
/** Not working fully - shall adjust in next round
->setBillingAddress($billingAddress)
->setShippingAddress($shippingAddress)
 */


/** @var $paymentInfoCheckmo Info */
$paymentInfoCheckmo = Bootstrap::getObjectManager()->create(
   Info::class
);


$methodInstance = Bootstrap::getObjectManager()->create(Checkmo::class);

$payment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Sales\Model\Order\Payment::class
);
//this is not needed $info = Bootstrap::getObjectManager()->create(\Magento\Payment\Model\InfoInterface::class);
//as payment implements infointerface
//all we need is payment and method instance
$payment->setAdditionalInformation('contract_id', 1);
$payment->setAdditionalInformation('oc_selected', true);

$payment->setMethodInstance($methodInstance);

//$order->getPayment()->setMethod('checkmo');
$payment->setMethod('checkmo');
$order->setPayment($payment);

$order->save();
$pendingPaymentOrderId = $order->getId();

var_dump("After save ".$order->getId());


