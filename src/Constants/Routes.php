<?php

namespace Heidelpay\Constants;

/**
 * Constant class for heidelpay routes
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\constants
 */
class Routes
{
    const API_TRANSACTION_BY_ID = 'payment/heidelpay/transactions/{id}';
    const API_TRANSACTION_BY_CUSTOMERID = 'payment/heidelpay/transactions/customer/{id}';

    const RESPONSE_URL = 'payment/heidelpay/response';
    const PUSH_NOTIFICATION_URL = 'payment/heidelpay/pushNotification';

    const CHECKOUT_SUCCESS = 'payment/heidelpay/checkoutSuccess';
    const CHECKOUT_CANCEL = 'payment/heidelpay/checkoutCancel';
}
