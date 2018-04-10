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
        $transaction = pluginApp(Transaction::class, $data);

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
    public function getTransactionByBasketId(int $id): Transaction
    {
        /** @var Transaction[] $result */
        $result = $this->database->query(Transaction::class)
            ->where('basketId', '=', $id)
            ->get();

        return $result[0];
    }

    /**
     * @inheritdoc
     */
    public function getTransactionsByCustomerId(int $customerId): array
    {
        /** @var Transaction[] $result */
        $result = $this->database->query(Transaction::class)
            ->where('customerId', '=', $customerId)
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
            ->where('transactionType', '=', $transactionType)
            ->get();

        return $result;
    }
}
