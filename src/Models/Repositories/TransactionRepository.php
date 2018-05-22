<?php

namespace Heidelpay\Models\Repositories;

use Heidelpay\Constants\TransactionType;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Plugin\Log\Loggable;

/**
 * heidelpay Transaction repository class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models\repositories
 */
class TransactionRepository implements TransactionRepositoryContract
{
    use Loggable;

    /**
     * @var DataBase
     */
    private $database;

    /**
     * TransactionRepository constructor.
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
    public function createTransaction(array $data): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = pluginApp(Transaction::class);

        $transaction->storeId = $data[Transaction::FIELD_SHOP_ID];
        $transaction->customerId = $data[Transaction::FIELD_CUSTOMER_ID];
        $transaction->txnId = $data[Transaction::FIELD_TRANSACTION_ID];
        $transaction->basketId = $data[Transaction::FIELD_BASKET_ID];
        $transaction->orderId = $data[Transaction::FIELD_ORDER_ID];
        $transaction->paymentMethodId = $data[Transaction::FIELD_PAYMENT_METHOD_ID];
        $transaction->status = $data[Transaction::FIELD_STATUS];
        $transaction->transactionType = $data[Transaction::FIELD_TRANSACTION_TYPE];
        $transaction->shortId = $data[Transaction::FIELD_SHORT_ID];
        $transaction->uniqueId = $data[Transaction::FIELD_UNIQUE_ID];
        $transaction->transactionDetails = $data[Transaction::FIELD_TRANSACTION_DETAILS];
        $transaction->transactionProcessing = $data[Transaction::FIELD_TRANSACTION_PROCESSING];
        $transaction->createdAt = $data[Transaction::FIELD_CREATED_AT];
        $transaction->updatedAt = $data[Transaction::FIELD_UPDATED_AT];

        if (isset($data['isClosed']) && $data['isClosed'] === true) {
            $transaction->isClosed = true;
        }

        $this->getLogger(__METHOD__)->error('transaction data', [
            'transaction' => $transaction
        ]);

        $transaction = $this->database->save($transaction);
        return $transaction;
    }

    /**
     * @inheritdoc
     */
    public function updateTransaction(Transaction $transaction): Transaction
    {
        if ($transaction->id !== null) {
            $transaction = $this->database->save($transaction);
        }

        return $transaction;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return Transaction
     */
    public function getTransactionByKeyValue(string $key, $value): Transaction
    {
        $result = $this->database->query(Transaction::class)
            ->where($key, '=', $value)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getTransactionById(int $id): Transaction
    {
        return $this->database->find(Transaction::class, $id);
    }

    /**
     * @inheritdoc
     */
    public function getTransactionsByTxnId($txnId): array
    {
        return $this->database->query(Transaction::class)
            ->where(Transaction::FIELD_TRANSACTION_ID, '=', $txnId)
            ->orderBy(Transaction::FIELD_ID, 'desc')
            ->get();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionsByCustomerId(int $customerId): array
    {
        /** @var Transaction[] $result */
        $result = $this->database->query(Transaction::class)
            ->where(Transaction::FIELD_CUSTOMER_ID, '=', $customerId)
            ->orderBy(Transaction::FIELD_ID, 'desc')
            ->get();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionsByType(string $transactionType = TransactionType::AUTHORIZE): array
    {
        /** @var Transaction[] $result */
        $result = $this->database->query(Transaction::class)
            ->where(Transaction::FIELD_TRANSACTION_TYPE, '=', $transactionType)
            ->get();

        return $result;
    }
}
