<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
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
        $orderFromStorage  = $sessionStorage->getOrder();
        $mopId   = $orderFromStorage->methodOfPayment;
        $txnId   = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);

        /** @var Order $order */
        $order = $args[0] ?? null;
        if (\is_array($order)) {
            foreach ($order['properties'] as $property) {
                if ($property['typeId'] === OrderPropertyType::PAYMENT_METHOD) {
                    $mopId = $property['value'];
                }
                if ($property['typeId'] === OrderPropertyType::EXTERNAL_ORDER_ID) {
                    $txnId = $property['value'];
                }
            }
        }
        $notificationService->error('Arguments: ', __METHOD__, ['MOP' => $mopId, 'TxnId' => $txnId]);

        /** @var PaymentMethodContract $paymentMethod */
        $paymentMethod = $helper->getPaymentMethodInstanceByMopId($mopId);
        if (!$paymentMethod->renderInvoiceData()) {
            return '';
        }

        $paymentDetails = $helper->getPaymentDetailsByTxnId($txnId);
        return $twig->render('Heidelpay::content/InvoiceDetails', $paymentDetails);
    }
}
