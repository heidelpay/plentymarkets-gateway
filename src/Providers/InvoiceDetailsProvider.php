<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    public function call(
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorage,
        TransactionRepositoryContract $transactionRepos,
        PaymentHelper $helper,
        NotificationServiceContract $notificationService
    ): string {
        $mopId = $sessionStorage->getOrder()->methodOfPayment;

        $notificationService->error('#1',__METHOD__);

        /** @var PaymentMethodContract $paymentMethod */
        $paymentMethod = $helper->getPaymentMethodInstanceByMopId($mopId);
        $notificationService->error('#2',__METHOD__,['mopId' => $mopId]);

        if (!$paymentMethod->renderInvoiceData()) {
            $notificationService->error('#3',__METHOD__,['return empty string']);
            return '';
        }

        $notificationService->error('#4',__METHOD__);
        $txnId = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $notificationService->error('#5',__METHOD__,['txnId' => $txnId]);
        $transaction = $transactionRepos->getTransactionsByTxnId($txnId)[0];
        $notificationService->error('#6',__METHOD__,['transcation' => $transaction]);

        $details = $transaction->transactionDetails;
        $accountIBAN = $details['CONNECTOR.ACCOUNT_IBAN'];
        $accountBIC = $details['CONNECTOR.ACCOUNT_BIC'];
        $accountHolder = $details['CONNECTOR.ACCOUNT_HOLDER'];
        $accountUsage = $details['CONNECTOR.ACCOUNT_USAGE'] ?? $transaction->shortId;

        $notificationService->error('#7',__METHOD__,['transcationDetails' => $details]);

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