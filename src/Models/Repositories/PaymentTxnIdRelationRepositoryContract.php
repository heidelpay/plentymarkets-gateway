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

use Heidelpay\Models\PaymentTxnIdRelation;

interface PaymentTxnIdRelationRepositoryContract
{
    /**
     * Creates existing PaymentTxnIdRelation object
     *
     * @param array $data
     * @return PaymentTxnIdRelation
     */
    public function createPaymentTxnIdRelation(array $data): PaymentTxnIdRelation;

    /**
     * Updates existing PaymentTxnIdRelation object
     *
     * @param $paymentTxnIdRelation
     * @return PaymentTxnIdRelation
     */
    public function updatePaymentTxnIdRelation($paymentTxnIdRelation): PaymentTxnIdRelation;

    /**
     * Returns the PaymentTxnIdRelation object with the given property value.
     *
     * @param string $key
     * @param        $value
     *
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationByKeyValue(string $key, $value): PaymentTxnIdRelation;

    /**
     * Returns the relation object stored with this id.
     *
     * @param int $objectId
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationById($objectId): PaymentTxnIdRelation;

    /**
     * Returns the relation object stored with this payment id.
     *
     * @param $paymentId
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationByPaymentId($paymentId): PaymentTxnIdRelation;

    /**
     * Return the relation object stored with this txnId
     *
     * @param $txnId
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationByTxnId($txnId): PaymentTxnIdRelation;

    /**
     * Return the payment id associated to the given txn id.
     *
     * @param $txnId
     * @return int
     */
    public function getPaymentIdByTxnId($txnId): int;
}
