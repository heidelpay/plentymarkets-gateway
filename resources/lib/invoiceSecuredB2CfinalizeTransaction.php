<?php
/**
 * Performs finalize transaction.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
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

try {
    if (!is_callable([$paymentMethod, $transactionType])) {
        throw new Exception('Invalid transaction type for InvoiceB2CSecured payment method (' . $transactionType . ')!');
    }

    $response = $paymentMethod->{$transactionType}(SdkRestApi::getParam('referenceId') ?: null);
} catch (Exception $e) {
    $errorResponse = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
    ];
}

// return the responseArray, if an exception has been thrown, otherwise return an array containing response results.
$responseObj   = $paymentMethod->getResponse();
$responseArray = $responseObj->toArray();
ksort($responseArray);
return $errorResponse ?? [
        'response' => $responseArray,
        'isSuccess' => $responseObj->isSuccess(),
        'isPending' => $responseObj->isPending(),
        'isError' => $responseObj->isError(),
];
