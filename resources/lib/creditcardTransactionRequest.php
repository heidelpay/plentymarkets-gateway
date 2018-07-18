<?php
/**
 * Performs credit card transaction requests.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\external-lib-callbacks
 */

use Heidelpay\PhpPaymentApi\Request;

/** @var array $requestParams */
$requestParams = SdkRestApi::getParam('request');
$transactionType = SdkRestApi::getParam('transactionType');

$creditCardPaymentMethod = new \Heidelpay\PhpPaymentApi\PaymentMethods\CreditCardPaymentMethod();
$creditCardPaymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

$refId = SdkRestApi::getParam('referenceId') ?: null;
$paymentFrameOrigin = $creditCardPaymentMethod->getRequest()->getFrontend()->getPaymentFrameOrigin();
$preventAsyncRedirect = $creditCardPaymentMethod->getRequest()->getFrontend()->getPreventAsyncRedirect();
$cssPath = $creditCardPaymentMethod->getRequest()->getFrontend()->getCssPath();

try {
    if (!is_callable([$creditCardPaymentMethod, $transactionType])) {
        throw new \Exception('Invalid transaction type for CreditCard payment method (' . $transactionType . ')!');
    }

    if ($refId !== null) {
        $response = $creditCardPaymentMethod->{$transactionType}(
            $refId,
            $paymentFrameOrigin,
            $preventAsyncRedirect,
            $cssPath
        );
    } else {
        $response = $creditCardPaymentMethod->{$transactionType}($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
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
    'response' => $creditCardPaymentMethod->getResponse()->toArray(),
    'isSuccess' => $creditCardPaymentMethod->getResponse()->isSuccess(),
    'isPending' => $creditCardPaymentMethod->getResponse()->isPending(),
    'isError' => $creditCardPaymentMethod->getResponse()->isError(),
];
