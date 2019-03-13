<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    public function call(
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorage,
        TransactionRepositoryContract $transactionRepos,
        PaymentHelper $helper
    ): string {
        $mopId = $sessionStorage->getOrder()->methodOfPayment;

        /** @var PaymentMethodContract $paymentMethod */
        $paymentMethod = $helper->getPaymentMethodInstanceByMopId($mopId);

        if (!$paymentMethod->renderInvoiceData()) {
            return '';
        }

        $txnId = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $transaction = $transactionRepos->getTransactionsByTxnId($txnId)[0];

        $details = $transaction->transactionDetails;
        $accountIBAN = $details['CONNECTOR.ACCOUNT_IBAN'];
        $accountBIC = $details['CONNECTOR.ACCOUNT_BIC'];
        $accountHolder = $details['CONNECTOR.ACCOUNT_HOLDER'];
        $accountUsage = $details['CONNECTOR.ACCOUNT_USAGE'] ?? $transaction->shortId;

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