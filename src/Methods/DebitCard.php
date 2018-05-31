<?php
/**
 * heidelpay Debit Card Payment Method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
namespace Heidelpay\Methods;

use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

class DebitCard extends AbstractMethod
{
    const CONFIG_KEY = 'debitcard';
    const KEY = 'DEBIT_CARD';
    const DEFAULT_NAME = 'Debit Card';
    const DEFAULT_ICON_PATH = '/images/logos/card_payment_icon.png';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_HTML;
    const INITIALIZE_PAYMENT = true;
    const FORM_TEMPLATE = '';
}
