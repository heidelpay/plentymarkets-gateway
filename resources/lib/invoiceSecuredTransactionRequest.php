<?php
/**
 * Performs credit card transaction requests.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\external-lib-callbacks
 */

use Heidelpay\PhpPaymentApi\Request;

/** @var array $requestParams */
$requestParams = SdkRestApi::getParam('request');
$transactionType = SdkRestApi::getParam('transactionType');

$paymentMethod = new \Heidelpay\PhpPaymentApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod();
$paymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

$refId = SdkRestApi::getParam('referenceId') ?: null;
$paymentFrameOrigin = $paymentMethod->getRequest()->getFrontend()->getPaymentFrameOrigin();
$preventAsyncRedirect = $paymentMethod->getRequest()->getFrontend()->getPreventAsyncRedirect();
$cssPath = $paymentMethod->getRequest()->getFrontend()->getCssPath();

try {
    if (!is_callable([$paymentMethod, $transactionType])) {
        throw new \Exception('Invalid transaction type for InvoiceB2CSecured payment method (' . $transactionType . ')!');
    }

    if ($refId !== null) {
        $response = $paymentMethod->{$transactionType}(
            $refId,
            $paymentFrameOrigin,
            $preventAsyncRedirect,
            $cssPath
        );
    } else {
        $response = $paymentMethod->{$transactionType}($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
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
        'response' => $paymentMethod->getResponse()->toArray(),
        'isSuccess' => $paymentMethod->getResponse()->isSuccess(),
        'isPending' => $paymentMethod->getResponse()->isPending(),
        'isError' => $paymentMethod->getResponse()->isError(),
];
