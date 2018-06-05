<?php
namespace Heidelpay\Models;

use Heidelpay\Constants\Plugin;

/**
 * The payment order relation model
 */
class PaymentTxnIdRelation
{
    const TABLE_NAME = 'transaction_payment_relation';

    const FIELD_ID = 'id';
    const FIELD_CREATED_AT = 'createdAt';
    const FIELD_UPDATED_AT = 'updatedAt';
    const FIELD_ASSIGNED_AT = 'assignedAt';
    const FIELD_PAYMENT_ID = 'paymentId';
    const FIELD_TRANSACTION_ID = 'transactionId';

    public $id;
    public $paymentId;
    public $transactionId;
    public $assignedAt;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return Plugin::NAME . '::' . self::TABLE_NAME;
    }
}
