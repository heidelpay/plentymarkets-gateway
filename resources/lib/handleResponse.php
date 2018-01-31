<?php
/**
 * heidelpay PHP Payment API Response handler
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway
 */

use Heidelpay\PhpPaymentApi\Response as HeidelpayResponse;

$response = HeidelpayResponse::fromPost(SdkRestApi::getParam('json_response'));

/** @return array */
return json_decode($response->toJson(), true);
