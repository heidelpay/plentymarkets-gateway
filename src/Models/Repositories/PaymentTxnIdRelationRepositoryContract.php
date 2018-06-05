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
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

interface PaymentTxnIdRelationRepositoryContract
{
    /**
     * @inheritdoc
     */
    public function createPaymentTxnIdRelation(array $data): PaymentTxnIdRelation;

    /**
     * @inheritdoc
     */
    public function updatePaymentTxnIdRelation($paymentTxnIdRelationRelation): Model;

    /**
     * @param string $key
     * @param        $value
     *
     * @return PaymentTxnIdRelation
     */
    public function getPaymentTxnIdRelationByKeyValue(string $key, $value): PaymentTxnIdRelation;

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationById(int $id): Model;

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByPaymentId($paymentId): array;

    /**
     * @inheritdoc
     */
    public function getPaymentTxnIdRelationByTransactionId($txnId): array;
}