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
 * @property int $orderId
 * @property int $paymentMethodId
 * @property int $status
 * @property string $transactionType
 * @property string $shortId
 * @property string $uniqueId
 * @property string $txnId
 * @property int $basketId
 * @property boolean $isClosed
 * @property array $transactionDetails
 * @property array $transactionProcessing
 * @property string $createdAt
 * @property string $updatedAt
 */
class Transaction extends Model
{
    const PROCESSING_CODE = 'code';
    const PROCESSING_REASON = 'reason';
    const PROCESSING_REASON_CODE = 'reason_code';
    const PROCESSING_RESULT = 'result';
    const PROCESSING_RETURN = 'return';
    const PROCESSING_RETURN_CODE = 'return_code';
    const PROCESSING_STATUS = 'status';
    const PROCESSING_STATUS_CODE = 'status_code';
    const PROCESSING_TIMESTAMP = 'timestamp';

    public $id = 0;
    public $storeId;
    public $customerId;
    public $orderId;
    public $paymentMethodId;
    public $status;
    public $transactionType = '';
    public $shortId = '';
    public $uniqueId = '';
    public $txnId = '';
    public $basketId = '';
    public $isClosed = false;
    public $transactionDetails = [];
    public $transactionProcessing = [];
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
