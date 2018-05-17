<?php

namespace Heidelpay\Services\Database;

use Heidelpay\Constants\TransactionFields;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Plenty\Plugin\Log\Loggable;

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
    use Loggable;

    const NO_ORDER_ID = -1;

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
     * Creates a Transaction entity
     *
     * @param array    $responseData
     * @param int|null $storeId
     * @param int|null $paymentMethodId
     * @param int|null $orderId
     *
     * @return Transaction
     */
    public function createTransaction(
        array $responseData,
        int $storeId = null,
        int $paymentMethodId = null,
        int $orderId = null
    ): Transaction {
        $this->getLogger(__METHOD__)->error('create Transaction', [
            'response' => $responseData,
            'storeId' => $storeId,
            'paymentMethodId' => $paymentMethodId,
            'orderId' => $orderId,
        ]);

        $heidelpayResponse = $responseData['response'];

        if ($storeId === null) {
            $storeId = (int) $heidelpayResponse['CRITERION.STORE_ID'];
        }

        if ($paymentMethodId === null) {
            $paymentMethodId = (int) $heidelpayResponse['CRITERION.MOP'];
        }

        $processingTimestamp = $heidelpayResponse['PROCESSING.TIMESTAMP'];

        $data = [];
        $data[TransactionFields::FIELD_TRANSACTION_ID] = $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $data[TransactionFields::FIELD_BASKET_ID] = $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $data[TransactionFields::FIELD_CUSTOMER_ID] = (int) $heidelpayResponse['IDENTIFICATION.SHOPPERID'];
        $data[TransactionFields::FIELD_SHOP_ID] = $storeId;
        $data[TransactionFields::FIELD_PAYMENT_METHOD_ID] = $paymentMethodId;
        $data[TransactionFields::FIELD_TRANSACTION_TYPE] =
            $this->paymentHelper->mapHeidelpayTransactionType($heidelpayResponse['PAYMENT.CODE']);
        $data[TransactionFields::FIELD_STATUS] = $this->paymentHelper->mapHeidelpayTransactionStatus($responseData);
        $data[TransactionFields::FIELD_SHORT_ID] = $heidelpayResponse['IDENTIFICATION.SHORTID'];
        $data[TransactionFields::FIELD_UNIQUE_ID] = $heidelpayResponse['IDENTIFICATION.UNIQUEID'];
        $data[TransactionFields::FIELD_CREATED_AT] = $processingTimestamp;
        $data[TransactionFields::FIELD_UPDATED_AT] = $processingTimestamp;

        // if the orderId is given, use this. else, use a dummy since null is not possible.
        $data[TransactionFields::FIELD_ORDER_ID] = $orderId ?? self::NO_ORDER_ID;

        $data[TransactionFields::FIELD_TRANSACTION_DETAILS] = $this->getTransactionDetails($heidelpayResponse);

        // transaction processing data
        $data[TransactionFields::FIELD_TRANSACTION_PROCESSING] = [
            Transaction::PROCESSING_CODE => $heidelpayResponse['PROCESSING.CODE'],
            Transaction::PROCESSING_REASON => $heidelpayResponse['PROCESSING.REASON'],
            Transaction::PROCESSING_REASON_CODE => $heidelpayResponse['PROCESSING.REASON_CODE'],
            Transaction::PROCESSING_RESULT => $heidelpayResponse['PROCESSING.RESULT'],
            Transaction::PROCESSING_RETURN => $heidelpayResponse['PROCESSING.RETURN'],
            Transaction::PROCESSING_RETURN_CODE => $heidelpayResponse['PROCESSING.RETURN_CODE'],
            Transaction::PROCESSING_STATUS => $heidelpayResponse['PROCESSING.STATUS'],
            Transaction::PROCESSING_STATUS_CODE => $heidelpayResponse['PROCESSING.STATUS_CODE'],
            Transaction::PROCESSING_TIMESTAMP => $processingTimestamp
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
        $groupPattern = '/^ADDRESS|^CONFIG|^CONTACT|^FRONTEND|^NAME|^PAYMENT|^PROCESSING|^USER/';

        // contains unnecessary parameter keys
        $toDelete = [
            'ACCOUNT.EXPIRY_YEAR', 'ACCOUNT.EXPIRY_MONTH', 'ACCOUNT.HOLDER', 'ACCOUNT.NUMBER', 'ACCOUNT.VERIFICATION',
            'CRITERION.PAYMENT_METHOD', 'CRITERION.PUSH_URL', 'CRITERION.SDK_NAME', 'CRITERION.SDK_VERSION',
            'CRITERION.SHOPMODULE_VERSION', 'CRITERION.SHOP_TYPE', 'IDENTIFICATION.SHOPPERID',
            'IDENTIFICATION.SHORTID', 'IDENTIFICATION.TRANSACTIONID', 'IDENTIFICATION.UNIQUEID',
            'PAYMENT.CODE', 'SECURITY.SENDER',
        ];

        foreach ($heidelpayData as $key => $value) {
            if (preg_match($groupPattern, $key) || \in_array($key, $toDelete, true)) {
                unset($heidelpayData[$key]);
            }
        }

        return $heidelpayData;
    }
}
