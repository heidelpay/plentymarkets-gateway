<?php
namespace Heidelpay\Models\Contracts;

use Heidelpay\Models\OrderTxnIdRelation;

/**
 * Interface for order txnId relation repository.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\models
 */
interface OrderTxnIdRelationRepositoryContract
{
    /**
     * Create relation object.
     *
     * @param int $orderId
     * @param string $txnId
     * @return OrderTxnIdRelation
     */
    public function createOrderTxnIdRelation(int $orderId, string $txnId): OrderTxnIdRelation;

    /**
     * Update relation object.
     *
     * @param $orderTxnIdRelation
     * @return OrderTxnIdRelation
     */
    public function updateOrderTxnIdRelation($orderTxnIdRelation): OrderTxnIdRelation;

    /**
     * Returns relation object by key value combination.
     * @param string $key
     * @param        $value
     *
     * @return OrderTxnIdRelation|null
     */
    public function getOrderTxnIdRelationByKeyValue(string $key, $value);

    /**
     * Returns relation object by order id.
     *
     * @param int $orderId
     * @return OrderTxnIdRelation|null
     */
    public function getOrderTxnIdRelationByOrderId(int $orderId);

    /**
     * Returns relation object by heidelpay transaction id.
     *
     * @param string $txnId
     * @return OrderTxnIdRelation|null
     *
     * @throws \RuntimeException
     */
    public function getOrderTxnIdRelationByTxnId(string $txnId);

    /**
     * Return the order id associated to the given txn id.
     *
     * @param string $txnId
     * @return int|null
     */
    public function getOrderIdByTxnId(string $txnId);
}
