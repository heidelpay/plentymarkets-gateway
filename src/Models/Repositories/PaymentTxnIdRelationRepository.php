<?php
/**
 * PaymentTxnIdRelationRepository class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models\repositories
 */
namespace Heidelpay\Models\Repositories;

use Heidelpay\Models\PaymentTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class PaymentTxnIdRelationRepository implements PaymentTxnIdRelationRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * PaymentTxnIdRelationRepository constructor.
     *
     * @param DataBase $dataBase
     */
    public function __construct(DataBase $dataBase)
    {
        $this->database = $dataBase;
    }

    /**
     * @inheritdoc
     */
    public function createPaymentTxnIdRelation(array $data): PaymentTxnIdRelation
    {
        /** @var PaymentTxnIdRelation $relation */
        $relation = pluginApp(PaymentTxnIdRelation::class);

        $now = date('Y-m-d H:i:s');
        $relation->transactionId = $data[PaymentTxnIdRelation::FIELD_TRANSACTION_ID];
        $relation->paymentId = $data[PaymentTxnIdRelation::FIELD_PAYMENT_ID];
        $relation->assignedAt = $relation->createdAt = $relation->updatedAt = $now;

        $relation = $this->database->save($relation);
        return $relation;
    }

    /**
     * @inheritdoc
     */
    public function updatePaymentTxnIdRelation($paymentTxnIdRelationRelation): Model
    {
        if ($paymentTxnIdRelationRelation->id !== null) {
            $paymentTxnIdRelationRelation = $this->database->save($paymentTxnIdRelationRelation);
        }

        return $paymentTxnIdRelationRelation;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationByKeyValue(string $key, $value): PaymentTxnIdRelation
    {
        $result = $this->database->query(PaymentTxnIdRelation::class)
            ->where($key, '=', $value)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationById(int $id): Model
    {
        return $this->database->find(PaymentTxnIdRelation::class, $id);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByPaymentId($paymentId): array
    {
        return $this->database->query(PaymentTxnIdRelation::class)
            ->where(PaymentTxnIdRelation::FIELD_PAYMENT_ID, '=', $paymentId)
            ->orderBy(PaymentTxnIdRelation::FIELD_ID, 'desc')
            ->get();
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByTransactionId($txnId): array
    {
        $result =  $this->database->query(PaymentTxnIdRelation::class)
            ->where(PaymentTxnIdRelation::FIELD_TRANSACTION_ID, '=', $txnId)
            ->orderBy(PaymentTxnIdRelation::FIELD_ID, 'desc')
            ->get();

        return $result[0];
    }
}
