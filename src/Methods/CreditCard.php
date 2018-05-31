<?php

namespace Heidelpay\Methods;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

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
    const DEFAULT_ICON_PATH = '/images/logos/card_payment_icon.png';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_HTML;
    const INITIALIZE_PAYMENT = true;
    const FORM_TEMPLATE = 'heidelpay::externalCardForm';
}
