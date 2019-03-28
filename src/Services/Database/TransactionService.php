<?php
/**
 * Transaction Service class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */
namespace Heidelpay\Services\Database;

use Heidelpay\Exceptions\SecurityHashInvalidException;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\SecretService;

class TransactionService
{
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
     * @param TransactionRepositoryContract $transactionRepo
     * @param PaymentHelper                 $helper
     */
    public function __construct(
        TransactionRepositoryContract $transactionRepo,
        PaymentHelper $helper
    ) {
        $this->paymentHelper = $helper;
        $this->transactionRepository = $transactionRepo;
    }

    /**
     * Creates a Transaction entity
     *
     * @param array    $responseData
     * @param int|null $storeId
     * @param int|null $paymentMethodId
     * @param int|null $orderId
     *
     * @throws \RuntimeException
     *
     * @return Transaction
     */
    public function createTransaction(
        array $responseData,
        int $storeId = null,
        int $paymentMethodId = null,
        int $orderId = null
    ): Transaction {
        $heidelpayResponse = $responseData['response'];

        $storeId = $storeId ?? (int)$heidelpayResponse['CRITERION.STORE_ID'];
        $paymentMethodId = $paymentMethodId ?? (int) $heidelpayResponse['CRITERION.MOP'];
        $processingTimestamp = $heidelpayResponse['PROCESSING.TIMESTAMP'];
        $transactionType = $this->paymentHelper->mapHeidelpayTransactionType($heidelpayResponse['PAYMENT.CODE']);

        $data = [];
        $data[Transaction::FIELD_TRANSACTION_ID] = $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $data[Transaction::FIELD_BASKET_ID] = $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $data[Transaction::FIELD_CUSTOMER_ID] = (int) $heidelpayResponse['IDENTIFICATION.SHOPPERID'];
        $data[Transaction::FIELD_SHOP_ID] = $storeId;
        $data[Transaction::FIELD_PAYMENT_METHOD_ID] = $paymentMethodId;
        $data[Transaction::FIELD_TRANSACTION_TYPE] = $transactionType;
        $data[Transaction::FIELD_STATUS] = $this->paymentHelper->mapHeidelpayTransactionStatus($responseData);
        $data[Transaction::FIELD_SHORT_ID] = $heidelpayResponse['IDENTIFICATION.SHORTID'];
        $data[Transaction::FIELD_UNIQUE_ID] = $heidelpayResponse['IDENTIFICATION.UNIQUEID'];
        $data[Transaction::FIELD_CREATED_AT] = $processingTimestamp;
        $data[Transaction::FIELD_UPDATED_AT] = $processingTimestamp;

        // if the orderId is given, use this. else, use a dummy since null is not possible.
        $data[Transaction::FIELD_ORDER_ID] = $orderId ?? self::NO_ORDER_ID;

        $data[Transaction::FIELD_TRANSACTION_DETAILS] = $this->getTransactionDetails($heidelpayResponse);

        // transaction processing data
        $data[Transaction::FIELD_TRANSACTION_PROCESSING] = [
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

        $txn = null;

        try {
            $txn = $this->transactionRepository->createTransaction($data);
        } catch (\Exception $e) {
            // no need to handle
        }

        if (!$txn instanceof Transaction) {
            throw new \RuntimeException('response.errorTransactionNotCreated');
        }

        return $txn;
    }

    /**
     * @param int $objectId
     *
     * @return Transaction
     */
    public function getTransactionById(int $objectId): Transaction
    {
        return $this->transactionRepository->getTransactionById($objectId);
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
            'PAYMENT.CODE', 'SECURITY.SENDER'
        ];

        foreach ($heidelpayData as $key => $value) {
            if (preg_match($groupPattern, $key) || \in_array($key, $toDelete, true)) {
                unset($heidelpayData[$key]);
            }
        }

        return $heidelpayData;
    }

    /**
     * @param $heidelpayResponse
     * @return Transaction|null
     */
    public function getTransactionIfItExists($heidelpayResponse)
    {
        return $this->transactionRepository->getTransactionsByShortId($heidelpayResponse['IDENTIFICATION.SHORTID']);
    }

    /**
     * Throws exception if the hash is invalid.
     *
     * @param $heidelpayResponse
     * @throws SecurityHashInvalidException
     * @throws \RuntimeException
     */
    public function verifyTransaction($heidelpayResponse)
    {
        if (!isset($heidelpayResponse['CRITERION.SECRET'])) {
            throw new SecurityHashInvalidException('general.errorSecretHashIsNotSet');
        }

        /** @var SecretService $secretService */
        $secretService = pluginApp(SecretService::class);

        $txnId = $heidelpayResponse['IDENTIFICATION.TRANSACTIONID'];
        $hash = $heidelpayResponse['CRITERION.SECRET'];

        $secretService->verifySecretHash($txnId, $hash);
    }
}
