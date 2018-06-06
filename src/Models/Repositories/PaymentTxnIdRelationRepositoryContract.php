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

namespace Heidelpay\Models\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

interface PaymentTxnIdRelationRepositoryContract
{
    /**
     * @inheritdoc
     */
    public function createPaymentTxnIdRelation(array $data): Model;

    /**
     * @inheritdoc
     */
    public function updatePaymentTxnIdRelation($paymentTxnIdRelationRelation): Model;

    /**
     * @param string $key
     * @param        $value
     *
     * @return Model
     */
    public function getPaymentTxnIdRelationByKeyValue(string $key, $value): Model;

    /**
     * Returns the relation object stored with this id.
     *
     * @param int $id
     * @return Model
     */
    public function getPaymentTxnIdRelationById(int $id): Model;

    /**
     * Returns the relation object stored with this payment id.
     *
     * @param $paymentId
     * @return Model
     */
    public function getPaymentTxnIdRelationByPaymentId($paymentId): Model;

    /**
     * Return the relation object stored with this txnId
     *
     * @param $txnId
     * @return Model
     */
    public function getPaymentTxnIdRelationByTxnId($txnId): Model;

    /**
     * Return the payment id associated to the given txn id.
     *
     * @param $txnId
     * @return int
     */
    public function getPaymentIdByTxnId($txnId): int;
}