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

use Heidelpay\Models\Contracts\PaymentTxnIdRelationRepositoryContract;
use Heidelpay\Models\PaymentTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

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
    public function updatePaymentTxnIdRelation($paymentTxnIdRelation): PaymentTxnIdRelation
    {
        if ($paymentTxnIdRelation->id !== null) {
            $paymentTxnIdRelation = $this->database->save($paymentTxnIdRelation);
        }

        /**
         * @var PaymentTxnIdRelation $paymentTxnIdRelation
         */
        return $paymentTxnIdRelation;
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
    public function getPaymentTxnIdRelationById($objectId): PaymentTxnIdRelation
    {
        return $this->database->find(PaymentTxnIdRelation::class, $objectId);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByPaymentId($paymentId): PaymentTxnIdRelation
    {
        $result = $this->database->query(PaymentTxnIdRelation::class)
            ->where(PaymentTxnIdRelation::FIELD_PAYMENT_ID, '=', $paymentId)
            ->orderBy(PaymentTxnIdRelation::FIELD_ID, 'desc')
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByTxnId($txnId): PaymentTxnIdRelation
    {
        $result =  $this->database->query(PaymentTxnIdRelation::class)
            ->where(PaymentTxnIdRelation::FIELD_TRANSACTION_ID, '=', $txnId)
            ->orderBy(PaymentTxnIdRelation::FIELD_ID, 'desc')
            ->get();

        return $result[0];
    }

    /**
     * Return the payment id associated to the given txn id.
     *
     * @param $txnId
     * @return int
     */
    public function getPaymentIdByTxnId($txnId): int
    {
        return $this->getPaymentTxnIdRelationByTxnId($txnId)->paymentId;
    }
}
