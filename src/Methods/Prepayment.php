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

class Prepayment extends AbstractMethod
{
    const CONFIG_KEY = 'prepayment';
    const KEY = 'PREPAYMENT';
    const DEFAULT_NAME = 'Prepayment';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_CONTINUE;
    const INITIALIZE_PAYMENT = false;
}
