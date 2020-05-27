<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Payment\Model\InfoInterface;

/** @var $creditCard Reach\Payment\Model\Cc */
$creditCard = Bootstrap::getObjectManager()
    ->create(Reach\Payment\Model\Cc::class);

/** @var $info Magento\Payment\Model\InfoInterface */

$info = $creditCard->getInfoInstance();
$ccNumber = 'fake';
$securityCode = 'fake';

$info->setCcNumber($ccNumber)
     ->setCcType('VI')
     ->setCcCid($securityCode);

//$path = 'payment/reach_cc/active';
//$creditCard->getReachHelper()->setCreditCardActive($path);

$creditCard->save();