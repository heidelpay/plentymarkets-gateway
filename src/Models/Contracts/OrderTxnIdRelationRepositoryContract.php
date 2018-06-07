<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/${Package}
 */

namespace Heidelpay\Models\Contracts;

use Heidelpay\Models\OrderTxnIdRelation;

interface OrderTxnIdRelationRepositoryContract
{
    /**
     * Create relation object.
     *
     * @param array $data
     * @return OrderTxnIdRelation
     */
    public function createOrderTxnIdRelation(array $data): OrderTxnIdRelation;

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
     * @return OrderTxnIdRelation
     */
    public function getOrderTxnIdRelationByKeyValue(string $key, $value): OrderTxnIdRelation;

    /**
     * Returns relation object by order id.
     *
     * @param int $orderId
     * @return OrderTxnIdRelation
     */
    public function getOrderTxnIdRelationByOrderId(int $orderId): OrderTxnIdRelation;

    /**
     * Returns relation object by heidelpay transaction id.
     *
     * @param string $txnId
     * @return OrderTxnIdRelation
     */
    public function getOrderTxnIdRelationByTxnId(string $txnId): OrderTxnIdRelation;

    /**
     * Return the order id associated to the given txn id.
     *
     * @param $txnId
     * @return int
     */
    public function getOrderIdByTxnId($txnId): int;
}
