<?php

namespace Heidelpay\Methods;

/**
 * heidelpay Sofort. Payment Method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
class SofortPaymentMethod extends AbstractPaymentMethod
{
    const CONFIG_KEY = 'sofort';
    const KEY = 'SOFORT';
    const DEFAULT_NAME = 'Sofort.';
}
