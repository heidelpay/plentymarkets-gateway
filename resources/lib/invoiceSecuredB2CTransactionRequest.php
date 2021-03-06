<?php
/**
 * Performs invoice secured b2c transaction requests.
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

use Heidelpay\PhpPaymentApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod;
use Heidelpay\PhpPaymentApi\Request;

/** @var array $requestParams */
$requestParams = SdkRestApi::getParam('request');
$transactionType = SdkRestApi::getParam('transactionType');

$paymentMethod = new InvoiceB2CSecuredPaymentMethod();
$paymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

$refId = SdkRestApi::getParam('referenceId') ?: null;
$paymentFrameOrigin = $paymentMethod->getRequest()->getFrontend()->getPaymentFrameOrigin();
$preventAsyncRedirect = $paymentMethod->getRequest()->getFrontend()->getPreventAsyncRedirect();
$cssPath = $paymentMethod->getRequest()->getFrontend()->getCssPath();

try {
    if (!is_callable([$paymentMethod, $transactionType])) {
        throw new Exception('Invalid transaction type for InvoiceB2CSecured payment method (' . $transactionType . ')!');
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
} catch (Exception $e) {
    $errorResponse = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
    ];
}

// return the responseArray, if an exception has been thrown.
// else, return an array containing response results.
$responseObj   = $paymentMethod->getResponse();
$responseArray = $responseObj->toArray();
ksort($responseArray);
return $errorResponse ?? [
        'response' => $responseArray,
        'isSuccess' => $responseObj->isSuccess(),
        'isPending' => $responseObj->isPending(),
        'isError' => $responseObj->isError(),
];
