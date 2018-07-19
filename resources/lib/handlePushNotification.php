<?php
/**
 * Handles heidelpay push messages.
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

$responseArray = null;
$heidelpayResponse = null;

try {
    $push = new \Heidelpay\PhpPaymentApi\Push(SdkRestApi::getParam('xmlContent'));
    $heidelpayResponse = $push->getResponse();
} catch (\Heidelpay\PhpPaymentApi\Exceptions\XmlResponseParserException $e) {
    $responseArray = [
        'exceptionCode' => $e->getCode(),
        'exceptionMsg' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString()
    ];
}

return $responseArray ?? [
    'response' => $heidelpayResponse->toArray(),
    'jsonResponse' => $heidelpayResponse->toJson(),
    'isSuccess' => $heidelpayResponse->isSuccess(),
    'isPending' => $heidelpayResponse->isPending(),
    'isError' => $heidelpayResponse->isError()
];
