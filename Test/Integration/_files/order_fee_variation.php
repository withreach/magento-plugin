<?php
//credit: got idea from a Magento book
use \Magento\TestFramework\Helper\Bootstrap;
use Magento\OfflinePayments\Model\Checkmo;

//Note:
//For cleaner representation and to keep files smaller possibly each of the order,
// quote data could be loaded from separate files (the way we are doing that for
// address.)
//But for the ease of correlating things I kept all of those together
// when working on M2 integration testing framework and learning corresponding conventions
// for the first time

$integrationTestSuitePath = __DIR__ . '/../../../../../../../dev/tests/integration/testsuite/';

require $integrationTestSuitePath . 'Magento/Sales/_files/default_rollback.php';
require $integrationTestSuitePath . 'Magento/Catalog/_files/product_simple.php';
/** @var \Magento\Catalog\Model\Product $product */

$addressData = include $integrationTestSuitePath . 'Magento/Sales/_files/address_data.php';

$objectManager = Bootstrap::getObjectManager();

$billingAddress_ai = $objectManager->create('Magento\Quote\Api\Data\AddressInterface', ['data' => $addressData]);
$billingAddress_ai->setAddressType('billing');



$billingAddress_oai = $objectManager->create('Magento\Sales\Api\Data\OrderAddressInterface', ['data' => $addressData]);
$billingAddress_oai->setAddressType('billing');

$shippingAddress_oai = clone $billingAddress_oai;
$shippingAddress_oai->setId(null)->setAddressType('shipping');

$shippingAddress_ai = clone $billingAddress_ai;
$shippingAddress_ai->setId(null)->setAddressType('shipping');


$store = Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Store\Model\StoreManagerInterface::class)
    ->getStore();

//$store->setCode('current');
$store->save();

/** @var \Magento\Quote\Model\Quote $quote */
$quote = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Quote\Model\Quote::class);
$quote->setCustomerIsGuest(true)
    ->setStoreId($store->getId())
    ->setReservedOrderId('test01')
    ->setBillingAddress($billingAddress_ai)
    ->setShippingAddress($shippingAddress_ai)
    ->addProduct($product);


$quote->getPayment()->setMethod('checkmo');
$quote->setIsMultiShipping('0');
//$quote->collectTotals();

$quoteRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Quote\Api\CartRepositoryInterface::class);
$quoteRepository->save($quote);




/** @var Magento\Sales\Model\Order\Payment $payment */
$payment = $objectManager->create(\Magento\Sales\Model\Order\Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation([
        'token_metadata' => [
            'token' => 'f34vjw',
            'customer_id' => 1
        ],
        'contract_id' => 1,
        'oc_selected' => true
    ]);

//The above could be done the way (as postede below) as well

//$payment->setAdditionalInformation('contract_id', 1);
//$payment->setAdditionalInformation('oc_selected', true);
//$order->getPayment()->setMethod('checkmo');
//$payment->setMethod('checkmo');

$methodInstance = Bootstrap::getObjectManager()->create(Checkmo::class);


//this is not needed $info = Bootstrap::getObjectManager()->create(\Magento\Payment\Model\InfoInterface::class);
//as payment implements infointerface
//all we need is payment and method instance


$payment->setMethodInstance($methodInstance);



$order->setPayment($payment);


/** @var \Magento\Sales\Model\Order\Item $orderItem */
$orderItem = $objectManager->create('Magento\Sales\Model\Order\Item');
$orderItem->setProductId($product->getId())->setQtyOrdered(2);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setProductType('simple');

$incrementId = '100000005';

/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->create('Magento\Sales\Model\Order');
$order->setIncrementId(
    $incrementId
)->setState(
    \Magento\Sales\Model\Order::STATE_PROCESSING
)->setStatus(
    $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
)->setSubtotal(
    54.45
)->setGrandTotal(
    54.45
)->setBaseSubtotal(
    54.45
)->setBaseGrandTotal(
    54.45
)->setCustomerIsGuest(
    true
)->setCustomerEmail(
    'customer@null.com'
)->setBillingAddress(
    $billingAddress_oai
)->setShippingAddress(
    $shippingAddress_oai
)->setStoreId(
    $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId()
)->addItem(
    $orderItem
)->setPayment(
    $payment
)->setQuote($quote)->setPayment($payment);



$order->save();

//Not needed for testing the SalesOrderPlaceAfterObserver class
//though
$session = $objectManager->get('Magento\Checkout\Model\Session');
$session->setLastRealOrderId($order->getIncrementId());
$session->setLastOrderId($order->getId());
$session->setLastQuoteId($order->getId());
$session->setLastSuccessQuoteId($order->getId());