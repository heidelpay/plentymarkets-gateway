<?php
/**
 * Performs paypal transaction requests.
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

$payPalPaymentMethod = new \Heidelpay\PhpPaymentApi\PaymentMethods\PayPalPaymentMethod();
$payPalPaymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

try {
    if (!is_callable([$payPalPaymentMethod, $transactionType])) {
        throw new \Exception('Invalid transaction type for PayPal payment method (' . $transactionType . ')!');
    }

    $response = $payPalPaymentMethod->{$transactionType}();
} catch (\Exception $e) {
    $responseArray = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
    ];
}

return $responseArray ?? [
    'response' => $payPalPaymentMethod->getResponse()->toArray(),
    'jsonResponse' => $response->toJson(),
    'isSuccess' => $payPalPaymentMethod->getResponse()->isSuccess(),
    'isPending' => $payPalPaymentMethod->getResponse()->isPending(),
    'isError' => $payPalPaymentMethod->getResponse()->isError()
];
