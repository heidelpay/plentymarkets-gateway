<?php

namespace Heidelpay\Methods;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

/**
 * heidelpay Direct Debit Payment Method
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
class DirectDebit extends AbstractMethod
{
    const CONFIG_KEY = 'directdebit';
    const KEY = 'DIRECT_DEBIT';
    const DEFAULT_NAME = 'Direct Debit';
    const ICON = '';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_HTML;
    const INITIALIZE_PAYMENT = false;
    const FORM_TEMPLATE = 'heidelpay::directDebitForm';
}
