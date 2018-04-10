<?php

namespace Heidelpay\Services\Database;

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;

/**
 * Transaction Service class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */
class TransactionService
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var TransactionRepositoryContract
     */
    private $transactionRepository;

    /**
     * TransactionService constructor.
     *
     * @param TransactionRepositoryContract $transactionRepository
     * @param PaymentHelper                 $helper
     */
    public function __construct(TransactionRepositoryContract $transactionRepository, PaymentHelper $helper)
    {
        $this->paymentHelper = $helper;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @param array    $heidelpayResponse
     * @param int      $storeId
     * @param int      $paymentMethodId
     * @param int|null $orderId
     *
     * @return Transaction
     */
    public function createTransaction(
        array $heidelpayResponse,
        int $storeId,
        int $paymentMethodId,
        int $orderId = null
    ): Transaction {
        $data = [];
        $data['basketId'] = (int) $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $data['customerId'] = (int) $heidelpayResponse['IDENTIFICATION.SHOPPERID'];
        $data['storeId'] = $storeId;
        $data['paymentMethodId'] = $paymentMethodId;
        $data['transactionType'] =
            $this->paymentHelper->mapHeidelpayTransactionType($heidelpayResponse['PAYMENT.CODE']);
        $data['status'] = $this->paymentHelper->mapHeidelpayTransactionStatus($heidelpayResponse);
        $data['shortId'] = $heidelpayResponse['IDENTIFICATION.SHORTID'];
        $data['uniqueId'] = $heidelpayResponse['IDENTIFICATION.UNIQUEID'];
        $data['createdAt'] = date('Y-m-d H:i:s');

        if ($orderId !== null) {
            $data['orderId'] = $orderId;
        }

        $data['transactionDetails'] = $this->getTransactionDetails($heidelpayResponse);

        // transaction processing data
        $data['transactionProcessing'] = [
            Transaction::PROCESSING_CODE => $heidelpayResponse['PROCESSING.CODE'],
            Transaction::PROCESSING_REASON => $heidelpayResponse['PROCESSING.REASON'],
            Transaction::PROCESSING_REASON_CODE => $heidelpayResponse['PROCESSING.REASON_CODE'],
            Transaction::PROCESSING_RESULT => $heidelpayResponse['PROCESSING.RESULT'],
            Transaction::PROCESSING_RETURN => $heidelpayResponse['PROCESSING.RETURN'],
            Transaction::PROCESSING_RETURN_CODE => $heidelpayResponse['PROCESSING.RETURN_CODE'],
            Transaction::PROCESSING_STATUS => $heidelpayResponse['PROCESSING.STATUS'],
            Transaction::PROCESSING_STATUS_CODE => $heidelpayResponse['PROCESSING.STATUS_CODE'],
            Transaction::PROCESSING_TIMESTAMP => $heidelpayResponse['PROCESSING.TIMESTAMP'],
        ];

        return $this->transactionRepository->createTransaction($data);
    }

    /**
     * @param int $id
     *
     * @return Transaction
     */
    public function getTransactionById(int $id): Transaction
    {
        return $this->transactionRepository->getTransactionById($id);
    }

    /**
     * @param int $customerId
     *
     * @return Transaction[]
     */
    public function getTransactionsByCustomerId(int $customerId): array
    {
        return $this->transactionRepository->getTransactionsByCustomerId($customerId);
    }

    /**
     * Filters unnecessary or better-not-to-save data from the
     * incoming array and returns the filtered one.
     *
     * @param array $heidelpayData
     *
     * @return array
     */
    private function getTransactionDetails(array $heidelpayData): array
    {
        // contains unnecessary parameter groups
        $groupPattern = '/^ADDRESS/^CONFIG/^CONTACT/^FRONTEND/^NAME/^PAYMENT/^USER/';

        // contains unnecessary parameter keys
        $toDelete = [
            'ACCOUNT.EXPIRY_YEAR', 'ACCOUNT.EXPIRY_MONTH', 'ACCOUNT.HOLDER', 'ACCOUNT.NUMBER', 'ACCOUNT.VERIFICATION',
            'CRITERION.PAYMENT_METHOD', 'CRITERION.PUSH_URL', 'CRITERION.SDK_NAME', 'CRITERION.SDK_VERSION',
            'CRITERION.SHOPMODULE_VERSION', 'CRITERION.SHOP_TYPE', 'PAYMENT.CODE', 'SECURITY.SENDER',
        ];

        foreach ($heidelpayData as $key => $value) {
            if (preg_match($groupPattern, $key) || \in_array($key, $toDelete, true)) {
                unset($heidelpayData[$key]);
            }
        }

        sort($heidelpayData);
        return $heidelpayData;
    }
}
