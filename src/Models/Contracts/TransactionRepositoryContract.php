<?php

namespace Heidelpay\Models\Contracts;

use Heidelpay\Models\Transaction;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * heidelpay Transaction Repository Contract
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package  heidelpay\plentymarkets-gateway\models
 */
interface TransactionRepositoryContract
{
    /**
     * Creates a heidelpay transaction data set.
     *
     * @param array $data
     *
     * @return Transaction
     */
    public function createTransaction(array $data): Transaction;

    /**
     * Searches for a Transaction by its ID and returns it, if present.
     *
     * @param int $id
     *
     * @return Transaction|Model
     */
    public function getTransactionById(int $id);

    /**
     * @param string $txnId
     *
     * @return Transaction[]
     */
    public function getTransactionsByTxnId($txnId): array;

    /**
     * @param string $shortId
     *
     * @return Transaction|null
     */
    public function getTransactionsByShortId($shortId);

    /**
     * @param int $customerId
     *
     * @return Transaction[]
     */
    public function getTransactionsByCustomerId(int $customerId): array;

    /**
     * @param string $transactionType
     *
     * @return array
     */
    public function getTransactionsByType(string $transactionType): array;

    /**
     * @param Transaction|Model $transaction
     *
     * @return Transaction|Model
     */
    public function updateTransaction($transaction);
}
