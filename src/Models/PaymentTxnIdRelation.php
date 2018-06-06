<?php
/**
 * heidelpay PaymentTxnIdRelation model class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models
 *
 * @property int $id
 * @property string $txnId
 * @property string $paymentId
 * @property string $createdAt
 * @property string $updatedAt
 * @property string $assignedAt
 */
namespace Heidelpay\Models;

use Heidelpay\Constants\Plugin;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * The payment order relation model
 */
class PaymentTxnIdRelation extends Model
{
    const TABLE_NAME = 'transaction_payment_relations';

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
