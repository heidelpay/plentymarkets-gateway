<?php

namespace Heidelpay\Models;

use Heidelpay\Constants\Database;
use Heidelpay\Constants\Plugin;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * heidelpay Transaction model class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models
 *
 * @property int $id
 * @property int $storeId
 * @property int $customerId
 * @property int $basketId
 * @property int $orderId
 * @property int $paymentMethodId
 * @property string $transactionType
 * @property string $shortId
 * @property string $uniqueId
 * @property array $transactionDetails
 * @property string $source
 * @property string $createdAt
 * @property string $updatedAt
 */
class Transaction extends Model
{
    public $id = 0;
    public $storeId;
    public $customerId;
    public $basketId;
    public $orderId;
    public $paymentMethodId;
    public $status;
    public $transactionType = '';
    public $shortId = '';
    public $uniqueId = '';
    public $transactionDetails = [];
    public $source = '';
    public $createdAt = '';
    public $updatedAt = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return Plugin::NAME . '::' . Database::TABLE_TRANSACTIONS;
    }
}
