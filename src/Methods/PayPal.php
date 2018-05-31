<?php
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
namespace Heidelpay\Methods;

use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

class PayPal extends AbstractMethod
{
    const CONFIG_KEY = 'paypal';
    const KEY = 'PAYPAL';
    const DEFAULT_NAME = 'PayPal';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL;
    const INITIALIZE_PAYMENT = true;
}
