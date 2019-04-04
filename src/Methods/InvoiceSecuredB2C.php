<?php
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

namespace Heidelpay\Methods;

use DateTime;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Helper\RequestHelper;
use Heidelpay\Services\BasketServiceContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Plugin\Http\Request;
use RuntimeException;

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
    const B2C_ONLY = true;
    const COUNTRY_RESTRICTION = ['DE', 'AT'];
    const ADDRESSES_MUST_MATCH = true;

    /** @var RequestHelper */
    private $requestHelper;

    /**
     * InvoiceSecuredB2C constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param MethodConfigContract $config
     * @param BasketServiceContract $basketService
     * @param RequestHelper $requestHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        MethodConfigContract $config,
        BasketServiceContract $basketService,
        RequestHelper $requestHelper
    ) {
        $this->requestHelper = $requestHelper;

        parent::__construct($paymentHelper, $config, $basketService);
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request)
    {
        $dob = $this->requestHelper->getDateOfBirth($request);

        // is valid date
        DateTime::createFromFormat('Y-m-d', $dob);
        if( DateTime::getLastErrors()['warning_count'] > 0 ){
            throw new RuntimeException('payment.errorDateOfBirthIsInvalid');
        }

        // is over 18
        if(time() < strtotime('+18 years', $dob))  {
            throw new RuntimeException('payment.errorUnder18');
        }

        $salutation = $this->requestHelper->getSalutation($request);
    }
}
