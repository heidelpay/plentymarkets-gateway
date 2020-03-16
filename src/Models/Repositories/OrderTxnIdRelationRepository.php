<?php
namespace Heidelpay\Models\Repositories;

use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\OrderTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use RuntimeException;

/**
 * OrderTxnIdRelationRepository class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models
 */
class OrderTxnIdRelationRepository implements OrderTxnIdRelationRepositoryContract
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
    public function createOrderTxnIdRelation(int $orderId, string $txnId, int $mopId): OrderTxnIdRelation
    {
        /** @var OrderTxnIdRelation $relation */
        $relation = pluginApp(OrderTxnIdRelation::class);

        $now = date('Y-m-d H:i:s');
        $relation->txnId = $txnId;
        $relation->orderId = $orderId;
        $relation->mopId = $mopId;
        $relation->assignedAt = $relation->createdAt = $relation->updatedAt = $now;

        $relation = $this->database->save($relation);
        return $relation;
    }

    /**
     * @inheritdoc
     */
    public function updateOrderTxnIdRelation($orderTxnIdRelation): OrderTxnIdRelation
    {
        if ($orderTxnIdRelation->id !== null) {
            $orderTxnIdRelation = $this->database->save($orderTxnIdRelation);
        }

        /**
         * @var OrderTxnIdRelation $orderTxnIdRelation
         */
        return $orderTxnIdRelation;
    }


    /**
     * {@inheritDoc}
     */
    public function getOrderTxnIdRelationByKeyValue(string $key, $value)
    {
        $result = $this->database->query(OrderTxnIdRelation::class)
            ->where($key, '=', $value)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getOrderTxnIdRelationByOrderId(int $orderId)
    {
        $result = $this->database->query(OrderTxnIdRelation::class)
            ->where(OrderTxnIdRelation::FIELD_ORDER_ID, '=', $orderId)
            ->get();

        return $result[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderTxnIdRelationByTxnId(string $txnId)
    {
        $result =  $this->database->query(OrderTxnIdRelation::class)
            ->where(OrderTxnIdRelation::FIELD_TXN_ID, '=', $txnId)
            ->get();

        return $result[0];
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function getOrderIdByTxnId(string $txnId): int
    {
        return $this->getOrderTxnIdRelationByTxnId($txnId)->orderId;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function createOrUpdateRelation(string $txnId, int $mopId, int $orderId = 0)
    {
        $relation = $this->getOrderTxnIdRelationByTxnId($txnId);
        if (!$relation instanceof OrderTxnIdRelation) {
            $relation = $this->createOrderTxnIdRelation($orderId, $txnId, $mopId);
        } else {
            $relation->orderId = $orderId;
            $relation->mopId = $orderId;
            $relation = $this->updateOrderTxnIdRelation($relation);
        }
        return $relation;
    }
}
