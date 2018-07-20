<?php

namespace Heidelpay\Controllers\Api;

use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;

/**
 * Transaction API controller class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\controllers
 */
class TransactionController
{
    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * TransactionController constructor.
     *
     * @param TransactionService $transactionService
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @param $transactionId
     *
     * @return Transaction
     */
    public function getTransactionById($transactionId): Transaction
    {
        return $this->transactionService->getTransactionById($transactionId);
    }

    /**
     * @param $customerId
     *
     * @return Transaction[]
     */
    public function getTransactionsByCustomerId($customerId): array
    {
        return $this->transactionService->getTransactionsByCustomerId($customerId);
    }
}
