<?php

namespace Heidelpay\Models\Contracts;

use Heidelpay\Models\Transaction;

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
 * @package heidelpay\plentymarkets-gateway\models\contracts
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
     * @return Transaction
     */
    public function getTransactionById(int $id): Transaction;

    /**
     * @param int $id
     *
     * @return array
     */
    public function getTransactionsByBasketId(int $id): array;

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
     * @param Transaction $transaction
     *
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction): Transaction;
}
