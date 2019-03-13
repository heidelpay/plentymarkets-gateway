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
        $accountIBAN = $details['CONNECTOR.ACCOUNT_IBAN'];
        $accountBIC = $details['CONNECTOR.ACCOUNT_BIC'];
        $accountHolder = $details['CONNECTOR.ACCOUNT_HOLDER'];
        $accountUsage = isset($details['CONNECTOR.ACCOUNT_USAGE']) ?: $transaction->shortId;

        $notificationService->error('remove me too', __METHOD__, [
            'accountIBAN' => $accountIBAN,
            'accountBIC' => $accountBIC,
            'accountHolder' => $accountHolder,
            'accountUsage' => $accountUsage,
            'shortId' => $transaction->shortId
        ]);

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