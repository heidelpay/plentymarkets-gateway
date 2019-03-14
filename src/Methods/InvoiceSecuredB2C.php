<?php

namespace Heidelpay\Methods;
use Heidelpay\Constants\TransactionType;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

/**
 * heidelpay Invoice Secured B2C Payment Method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
class InvoiceSecuredB2C extends AbstractMethod
{
    const CONFIG_KEY = 'invoicesecuredb2c';
    const KEY = 'INVOICE_SECURED_B2C';
    const DEFAULT_NAME = 'Invoice Secured';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_HTML;
    const TRANSACTION_TYPE = TransactionType::AUTHORIZE;
    const INITIALIZE_PAYMENT = false;
    const FORM_TEMPLATE = 'Heidelpay::invoiceSecuredB2CForm';
    const NEEDS_CUSTOMER_INPUT = false;
    const NEEDS_BASKET = true;
    const RENDER_INVOICE_DATA = true;
}
