<?php

namespace Heidelpay\Methods;
use Heidelpay\Constants\TransactionType;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

/**
 * heidelpay Sofort. Payment Method
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
class Sofort extends AbstractMethod
{
    const CONFIG_KEY = 'sofort';
    const KEY = 'SOFORT';
    const DEFAULT_NAME = 'Sofort.';
    const TRANSACTION_TYPE = TransactionType::AUTHORIZE;
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL;
    const INITIALIZE_PAYMENT = true;
    const CREATE_ORDER_BEFORE_REDIRECT = true;
}
