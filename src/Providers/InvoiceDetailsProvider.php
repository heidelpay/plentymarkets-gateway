<?php
namespace Heidelpay\Providers;

use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Plugin\Templates\Twig;

class InvoiceDetailsProvider
{
    public function call(
        Twig $twig,
        NotificationServiceContract $notificationService,
        $args
    ): string {
        $notificationService->error(self::class, __METHOD__, ['args' => $args]);


    }
//        $mop = $service->getOrderMopId();
//        $orderId = null;
//        $content = '';
//
//        /*
//         * Load the method of payment id from the order
//         */
//        $order = $args[0];
//        if($order instanceof Order) {
//            $orderId = $order->id;
//            foreach ($order->properties as $property) {
//                if($property->typeId == 3) {
//                    $mop = $property->value;
//                    break;
//                }
//            }
//        } elseif(is_array($order)) {
//            $orderId = $order['id'];
//            foreach ($order['properties'] as $property) {
//                if($property['typeId'] == 3) {
//                    $mop = $property['value'];
//                    break;
//                }
//            }
//        }
//
//        if($mop ==$invoiceHelper->getInvoiceMopId())
//        {
//            $lang = $service->getLang();
//            if($settings->getSetting('showBankData', $lang))
//            {
//                $content .= $twig->render('Invoice::BankDetails');
//            }
//
//            if($settings->getSetting('showDesignatedUse', $lang))
//            {
//                $content .=  $twig->render('Invoice::DesignatedUse', ['orderId'=>$orderId]);
//            }
//        }
//
//        return $content;
//    }
}