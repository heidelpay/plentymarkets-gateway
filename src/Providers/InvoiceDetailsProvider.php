<?php
namespace Heidelpay\Providers;

use Heidelpay\Constants\SessionKeys;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Services\NotificationServiceContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Models\Order;
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
        $transactions = $transactionRepos->getTransactionsByTxnId($txnId);

        $notificationService->error('remove me ', __METHOD__, ['txn' => $transactions]);

        return $twig->render('Heidelpay::content/InvoiceDetails');
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