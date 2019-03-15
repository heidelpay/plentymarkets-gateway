<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    public function call(
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorage,
        PaymentHelper $helper
    ): string {
        $mopId = $sessionStorage->getOrder()->methodOfPayment;

        /** @var PaymentMethodContract $paymentMethod */
        $paymentMethod = $helper->getPaymentMethodInstanceByMopId($mopId);
        if (!$paymentMethod->renderInvoiceData()) {
            return '';
        }

        $txnId = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $paymentDetails = $helper->getPaymentDetailsByTxnId($txnId);
        return $twig->render('Heidelpay::content/InvoiceDetails', $paymentDetails);
    }
}
