<?php
/**
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\external-lib-callbacks
 */

use Heidelpay\PhpPaymentApi\Request;

/** @var array $requestParams */
$requestParams = SdkRestApi::getParam('request');
$transactionType = SdkRestApi::getParam('transactionType');

$debitCardPaymentMethod = new \Heidelpay\PhpPaymentApi\PaymentMethods\DebitCardPaymentMethod();
$debitCardPaymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

$refId = SdkRestApi::getParam('referenceId') ?: null;
$paymentFrameOrigin = $debitCardPaymentMethod->getRequest()->getFrontend()->getPaymentFrameOrigin();
$preventAsyncRedirect = $debitCardPaymentMethod->getRequest()->getFrontend()->getPreventAsyncRedirect();
$cssPath = $debitCardPaymentMethod->getRequest()->getFrontend()->getCssPath();

try {
    if (!is_callable([$debitCardPaymentMethod, $transactionType])) {
        throw new \Exception('Invalid transaction type for DebitCard payment method (' . $transactionType . ')!');
    }

    if ($refId !== null) {
        $response = $debitCardPaymentMethod->{$transactionType}(
            $refId,
            $paymentFrameOrigin,
            $preventAsyncRedirect,
            $cssPath
        );
    } else {
        $response = $debitCardPaymentMethod->{$transactionType}($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
    }
} catch (\Exception $e) {
    $responseArray = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
    ];
}

// return the responseArray, if an exception has been thrown.
// else, return an array containing response results.
return $responseArray ?? [
    'response' => $debitCardPaymentMethod->getResponse()->toArray(),
    'isSuccess' => $debitCardPaymentMethod->getResponse()->isSuccess(),
    'isPending' => $debitCardPaymentMethod->getResponse()->isPending(),
    'isError' => $debitCardPaymentMethod->getResponse()->isError(),
];
