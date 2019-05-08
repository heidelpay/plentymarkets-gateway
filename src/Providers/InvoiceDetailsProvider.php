<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Heidelpay\Methods\PaymentMethodContract;
use function is_array;
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
     * @param array $args
     * @return string
     */
    public function call(
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorage,
        PaymentHelper $helper,
        $args
    ): string {
        $orderFromStorage  = $sessionStorage->getOrder();
        $mopId   = $orderFromStorage->methodOfPayment;
        $txnId   = $sessionStorage->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);

        /** @var Order $order */
        $order = $args[0] ?? null;
        if ($order instanceof Order) {
            $order = $order->toArray();
        }

        if (is_array($order)) {
            foreach ($order['properties'] as $property) {
                if ($property['typeId'] === OrderPropertyType::PAYMENT_METHOD) {
                    $mopId = $property['value'];
                }
                if ($property['typeId'] === OrderPropertyType::EXTERNAL_ORDER_ID) {
                    $txnId = $property['value'];
                }
            }
        }

        /** @var PaymentMethodContract $paymentMethod */
        $paymentMethod = $helper->getPaymentMethodInstanceByMopId($mopId);
        if (!$paymentMethod instanceof AbstractMethod && !$paymentMethod->renderInvoiceData()) {
            return '';
        }

        $paymentDetails = $helper->getPaymentDetailsByTxnId($txnId);
        return $twig->render('Heidelpay::content/InvoiceDetails', $paymentDetails);
    }
}
