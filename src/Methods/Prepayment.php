<?php

namespace Heidelpay\Methods;

/**
 * heidelpay Prepayment Payment Method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
class Prepayment extends AbstractMethod
{
    const CONFIG_KEY = 'prepayment';
    const KEY = 'PREPAYMENT';
    const DEFAULT_NAME = 'Prepayment';
}
