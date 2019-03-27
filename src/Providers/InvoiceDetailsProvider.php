<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    /**
     * @param Twig $twig
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param PaymentHelper $helper
     * @param NotificationServiceContract $notificationService
     * @param array $args
     * @return string
     */
    public function call(
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorage,
        PaymentHelper $helper,
        NotificationServiceContract $notificationService,
        $args
    ): string {
        $notificationService->error('Arguments: ', __METHOD__, ['ARGS' => $args]);

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
