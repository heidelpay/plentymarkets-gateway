<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/plentymarkets_gateway
 */
namespace Heidelpay\Constants;

class OrderStatus
{
    const INCOMPLETE_DATA = 1;
    const WAITING_FOR_PAYMENT_AND_ACTIVATION = 1.1;
    const ACTIVATED_WAITING_FOR_PAYMENT = 1.2;

    const WAITING_FOR_ACTIVATION = 2;

    const WAITING_FOR_PAYMENT = 3;
    const START_PAYPAL_PAYMENT_PROCESS = 3;
    const IN_WAITING_POSITION = 3.2;
    const READY_FOR_SHIPPING_WAITING_FOR_PAYMENT = 3.3;
    const DUNNING_LETTER_SENT = 3.4;

    const IN_PREPARATION_FOR_SHIPPING = 4;

    const CLEARED_FOR_SHIPPING = 5;
    const EXTERNAL_PROCESSING = 5.1;

    const CURRENTLY_BEING_SHIPPED = 6;

    const OUTGOING_ITEMS_BOOKED = 7;
    const ORDER_EXPORTED = 7.1;

    const CANCELED = 8;
    const CANCELED_BY_CUSTOMER = 8.1;

    const RETURN = 9;
    const ITEMS_ARE_CHECKED = 9.1;
    const WAITING_FOR_RETURN_FROM_WHOLESALE_DEALER = 9.2;
    const WARRANTY_INITIATED = 9.3;
    const EXCHANGE_INITIATED = 9.4;
    const CREDIT_NOTE_CREATED = 9.5;

    const WARRANTY = 10;

    const CREDIT_NOTE = 11;
    const CREDIT_NOTE_DISBURSED = 11.1;

    const REPAIR = 12;

    const MULTI_ORDER = 13;

    const MULTI_CREDIT_NOTE = 14;
}
