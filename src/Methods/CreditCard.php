<?php

namespace Heidelpay\Methods;

/**
 * heidelpay Credit Card Payment Method
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
class CreditCard extends AbstractMethod
{
    const CONFIG_KEY = 'creditcard';
    const KEY = 'CREDIT_CARD';
    const DEFAULT_NAME = 'Credit Card';
    const ICON = '/images/logos/cc_icon_xs.png';
}
