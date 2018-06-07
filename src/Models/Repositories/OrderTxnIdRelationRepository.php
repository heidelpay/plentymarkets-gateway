<?php
/**
 * OrderTxnIdRelationRepository class
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

use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\OrderTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

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
    public function createOrderTxnIdRelation(array $data): OrderTxnIdRelation
    {
        /** @var OrderTxnIdRelation $relation */
        $relation = pluginApp(OrderTxnIdRelation::class);

        $now = date('Y-m-d H:i:s');
        $relation->txnId = $data[OrderTxnIdRelation::FIELD_TXN_ID];
        $relation->orderId = $data[OrderTxnIdRelation::FIELD_ORDER_ID];
        $relation->mopId = $data[OrderTxnIdRelation::FIELD_MOP_ID];
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
     * @param string $key
     * @param        $value
     *
     * @return OrderTxnIdRelation
     */
    public function getOrderTxnIdRelationByKeyValue(string $key, $value): OrderTxnIdRelation
    {
        $result = $this->database->query(OrderTxnIdRelation::class)
            ->where($key, '=', $value)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getOrderTxnIdRelationByOrderId(int $orderId): OrderTxnIdRelation
    {
        $result = $this->database->query(OrderTxnIdRelation::class)
            ->where(OrderTxnIdRelation::FIELD_ORDER_ID, '=', $orderId)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getOrderTxnIdRelationByTxnId(string $txnId): OrderTxnIdRelation
    {
        $result =  $this->database->query(OrderTxnIdRelation::class)
            ->where(OrderTxnIdRelation::FIELD_TXN_ID, '=', $txnId)
            ->get();

        return $result[0];
    }

    /**
     * Return the order id associated to the given txn id.
     *
     * @param $txnId
     * @return int
     */
    public function getOrderIdByTxnId($txnId): int
    {
        return $this->getOrderTxnIdRelationByTxnId($txnId)->orderId;
    }
}
