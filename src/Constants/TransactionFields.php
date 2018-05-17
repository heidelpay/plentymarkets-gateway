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
 * @package  heidelpay/${Package}
 */
namespace Heidelpay\Constants;

class TransactionFields
{
    const FIELD_ID = 'id';
    const FIELD_SHOP_ID = 'storeId';
    const FIELD_CUSTOMER_ID = 'customerId';
    const FIELD_TRANSACTION_ID = 'txnId';
    const FIELD_BASKET_ID = 'basketId';
    const FIELD_ORDER_ID = 'orderId';
    const FIELD_PAYMENT_METHOD_ID = 'paymentMethodId';
    const FIELD_STATUS = 'status';
    const FIELD_TRANSACTION_TYPE = 'transactionType';
    const FIELD_SHORT_ID = 'shortId';
    const FIELD_UNIQUE_ID = 'uniqueId';
    const FIELD_TRANSACTION_DETAILS = 'transactionDetails';
    const FIELD_TRANSACTION_PROCESSING = 'transactionProcessing';
    const FIELD_CREATED_AT = 'createdAt';
    const FIELD_UPDATED_AT = 'updatedAt';
}
