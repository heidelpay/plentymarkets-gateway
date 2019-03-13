<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    public function call(
        Twig $twig,
        NotificationServiceContract $notificationService,
        FrontendSessionStorageFactoryContract $sessionStorage,
        TransactionRepositoryContract $transactionRepos
    ): string {
        // render bank details
        $txnId = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $transaction = $transactionRepos->getTransactionsByTxnId($txnId)[0];

        $notificationService->error('remove me ', __METHOD__, ['txn' => $transaction]);

        $details = $transaction->transactionDetails;
        $accountIBAN = $details['CONNECTOR_ACCOUNT_IBAN'];
        $accountBIC = $details['CONNECTOR_ACCOUNT_BIC'];
        $accountHolder = $details['CONNECTOR_ACCOUNT_HOLDER'];
        $accountUsage = isset($details['CONNECTOR_ACCOUNT_USAGE']) ?: $details['IDENTIFICATION_SHORTID'];

        return $twig->render(
            'Heidelpay::content/InvoiceDetails',
            [
                'accountIBAN' => $accountIBAN,
                'accountBIC' => $accountBIC,
                'accountHolder' => $accountHolder,
                'accountUsage' => $accountUsage
            ]
        );
    }
}