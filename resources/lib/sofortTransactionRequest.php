<?php
/**
 * Performs sofort. transaction requests.
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

$sofortPaymentMethod = new \Heidelpay\PhpPaymentApi\PaymentMethods\SofortPaymentMethod();
$sofortPaymentMethod->setRequest(Request::fromPost($requestParams));

$responseArray = null;

try {
    if (!is_callable([$sofortPaymentMethod, $transactionType])) {
        throw new \Exception('Invalid transaction type for Sofort payment method (' . $transactionType . ')!');
    }

    $response = $sofortPaymentMethod->{$transactionType}();
} catch (\Exception $e) {
    $responseArray = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
    ];
}

return $responseArray ?? [
    'response' => $sofortPaymentMethod->getResponse()->toArray(),
    'jsonResponse' => $response->toJson(),
    'isSuccess' => $sofortPaymentMethod->getResponse()->isSuccess(),
    'isPending' => $sofortPaymentMethod->getResponse()->isPending(),
    'isError' => $sofortPaymentMethod->getResponse()->isError()
];
