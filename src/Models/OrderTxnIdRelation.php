<?php

namespace Heidelpay\Models;

use Heidelpay\Constants\Plugin;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * heidelpay OrderTxnIdRelation model class
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
 * @property int $orderId
 * @property string $txnId
 * @property int $mopId
 * @property string $assignedAt
 * @property string $createdAt
 * @property string $updatedAt
 */
class OrderTxnIdRelation extends Model
{
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'orderId';
    const FIELD_TXN_ID = 'txnId';
    const FIELD_MOP_ID = 'mopId';
    const FIELD_ASSIGNED_AT = 'assignedAt';
    const FIELD_CREATED_AT = 'createdAt';
    const FIELD_UPDATED_AT = 'updatedAt';

    public $id = 0;
    public $orderId;
    public $txnId;
    public $mopId;
    public $assignedAt;
    public $createdAt;
    public $updatedAt;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return Plugin::NAME . '::orderTxnIdRelations';
    }
}
