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
        $data['basketId'] = (int) $heidelpayResponse['IDENTIFICATION_TRANSACTIONID'];
        $data['customerId'] = (int) $heidelpayResponse['IDENTIFICATION_SHOPPERID'];
        $data['storeId'] = $storeId;
        $data['paymentMethodId'] = $paymentMethodId;
        $data['transactionType'] =
            $this->paymentHelper->mapHeidelpayTransactionType($heidelpayResponse['PAYMENT_CODE']);
        $data['status'] = $this->paymentHelper->mapHeidelpayTransactionStatus($heidelpayResponse);
        $data['shortId'] = $heidelpayResponse['IDENTIFICATION_SHORTID'];
        $data['uniqueId'] = $heidelpayResponse['IDENTIFICATION_UNIQUEID'];
        $data['createdAt'] = date('Y-m-d H:i:s');

        if ($orderId !== null) {
            $data['orderId'] = $orderId;
        }

        $data['transactionDetails'] = $this->getTransactionDetails($heidelpayResponse);

        // transaction processing data
        $data['transactionProcessing'] = [
            Transaction::PROCESSING_CODE => $heidelpayResponse['PROCESSING_CODE'],
            Transaction::PROCESSING_REASON => $heidelpayResponse['PROCESSING_REASON'],
            Transaction::PROCESSING_REASON_CODE => $heidelpayResponse['PROCESSING_REASON_CODE'],
            Transaction::PROCESSING_RESULT => $heidelpayResponse['PROCESSING_RESULT'],
            Transaction::PROCESSING_RETURN => $heidelpayResponse['PROCESSING_RETURN'],
            Transaction::PROCESSING_RETURN_CODE => $heidelpayResponse['PROCESSING_RETURN_CODE'],
            Transaction::PROCESSING_STATUS => $heidelpayResponse['PROCESSING_STATUS'],
            Transaction::PROCESSING_STATUS_CODE => $heidelpayResponse['PROCESSING_STATUS_CODE'],
            Transaction::PROCESSING_TIMESTAMP => $heidelpayResponse['PROCESSING_TIMESTAMP'],
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
            'ACCOUNT_EXPIRY_YEAR', 'ACCOUNT_EXPIRY_MONTH', 'ACCOUNT_HOLDER', 'ACCOUNT_NUMBER', 'ACCOUNT_VERIFICATION',
            'CRITERION_PAYMENT_METHOD', 'CRITERION_PUSH_URL', 'CRITERION_SDK_NAME', 'CRITERION_SDK_VERSION',
            'CRITERION_SHOPMODULE_VERSION', 'CRITERION_SHOP_TYPE', 'PAYMENT_CODE', 'SECURITY_SENDER',
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
