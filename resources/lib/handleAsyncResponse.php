<?php
/**
 * Handles heidelpay async responses.
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

use Heidelpay\PhpPaymentApi\Response as HeidelpayResponse;

$response = HeidelpayResponse::fromPost(SdkRestApi::getParam('response'));

/** @return array */
return [
    'response' => $response->toArray(),
    'isSuccess' => $response->isSuccess(),
    'isPending' => $response->isPending(),
    'isError' => $response->isError(),
];
