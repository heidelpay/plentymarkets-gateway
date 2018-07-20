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
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\constants
 */
class Routes
{
    const BASE_URI = 'payment/' . Plugin::NAME . '/';
    const API_TRANSACTION_BY_ID = self::BASE_URI . 'transactions/{transactionId}';
    const API_TRANSACTION_BY_CUSTOMERID = self::BASE_URI . 'transactions/customer/{customerId}';

    const RESPONSE_URL = self::BASE_URI . 'response';
    const PUSH_NOTIFICATION_URL = self::BASE_URI . 'pushNotification';

    const CHECKOUT_SUCCESS = self::BASE_URI . 'checkoutSuccess';
    const CHECKOUT_CANCEL = self::BASE_URI . 'checkoutCancel';
}
