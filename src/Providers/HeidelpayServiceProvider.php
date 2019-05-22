<?php

namespace Heidelpay\Providers;

use Heidelpay\Configs\MainConfig;
use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfig;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\OrderModelHelper;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Repositories\OrderTxnIdRelationRepository;
use Heidelpay\Models\Repositories\TransactionRepository;
use Heidelpay\Services\BasketService;
use Heidelpay\Services\BasketServiceContract;
use Heidelpay\Services\NotificationService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\OrderService;
use Heidelpay\Services\OrderServiceContract;
use Heidelpay\Services\PaymentInfoService;
use Heidelpay\Services\PaymentInfoServiceContract;
use Heidelpay\Services\PaymentService;
use Heidelpay\Services\ResponseService;
use Heidelpay\Services\ResponseServiceContract;
use Heidelpay\Services\UrlService;
use Heidelpay\Services\UrlServiceContract;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Pdf\Events\OrderPdfGenerationEvent;
use Plenty\Modules\Order\Pdf\Models\OrderPdfGeneration;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;

/**
 * heidelpay Service Provider
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\providers
 */
class HeidelpayServiceProvider extends ServiceProvider
{
    /**
     * Register the heidelpay Service Providers.
     */
    public function register()
    {
        $app = $this->getApplication();
        $app->register(HeidelpayRouteServiceProvider::class);
        $app->bind(TransactionRepositoryContract::class, TransactionRepository::class);
        $app->bind(MainConfigContract::class, MainConfig::class);
        $app->bind(MethodConfigContract::class, MethodConfig::class);
        $app->bind(NotificationServiceContract::class, NotificationService::class);
        $app->bind(OrderTxnIdRelationRepositoryContract::class, OrderTxnIdRelationRepository::class);
        $app->bind(UrlServiceContract::class, UrlService::class);
        $app->bind(BasketServiceContract::class, BasketService::class);
        $app->bind(OrderServiceContract::class, OrderService::class);
        $app->bind(PaymentInfoServiceContract::class, PaymentInfoService::class);
        $app->bind(ResponseServiceContract::class, ResponseService::class);
    }

    /**
     * Boot the heidelpay Service Provider
     * Register payment methods, add event listeners, ...
     *
     * @param PaymentHelper $paymentHelper
     * @param PaymentMethodContainer $methodContainer
     * @param PaymentService $paymentService
     * @param Dispatcher $eventDispatcher
     * @param OrderModelHelper $modelHelper
     * @param PaymentInfoServiceContract $paymentInfoService
     */
    public function boot(
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $methodContainer,
        PaymentService $paymentService,
        Dispatcher $eventDispatcher,
        OrderModelHelper $modelHelper,
        PaymentInfoServiceContract $paymentInfoService
    ) {
        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethodClass */
        foreach (MethodConfig::getPaymentMethods() as $paymentMethodClass) {
            // register the payment method in the payment method container
            $methodContainer->register(
                $paymentHelper->getPluginPaymentMethodKey($paymentMethodClass),
                $paymentMethodClass,
                $paymentHelper->getPaymentMethodEventList()
            );
        }

        // listen for the event that gets the payment method content
        $eventDispatcher->listen(
            GetPaymentMethodContent::class,
            static function (GetPaymentMethodContent $event) use (
                $paymentHelper,
                $paymentService
            ) {
                $mop = $event->getMop();
                $paymentMethod = $paymentHelper->mapMopToPaymentMethod($mop);

                if (!empty($paymentMethod)) {
                    list($type, $value) = $paymentService->getPaymentMethodContent($paymentMethod, $mop);

                    $event->setValue($value);
                    $event->setType($type);
                }
            }
        );

        // listen for the event that executes the payment
        $eventDispatcher->listen(
            ExecutePayment::class,
            static function (ExecutePayment $event) use (
                $paymentHelper,
                $paymentService
            ) {
                $mop = $event->getMop();
                $paymentMethod = $paymentHelper->mapMopToPaymentMethod($mop);

                if (!empty($paymentMethod)) {
                    list($type, $value) = $paymentService->executePayment($paymentMethod, $event);

                    $event->setValue($value);
                    $event->setType($type);
                }
            }
        );

        // handle document generation
        $eventDispatcher->listen(
            OrderPdfGenerationEvent::class,
            static function (OrderPdfGenerationEvent $event) use (
                $paymentHelper, $paymentInfoService, $paymentService, $modelHelper
            ) {
                /** @var Order $order */
                $order = $event->getOrder();
                $docType = $event->getDocType();
                $mopId = $order->methodOfPaymentId;

                /** @var AbstractMethod $paymentMethod */
                $paymentMethod = $paymentHelper->getPaymentMethodInstanceByMopId($mopId);

                if (!$paymentMethod instanceof AbstractMethod) {
                    return;
                }

                switch ($docType) {
                    case Document::INVOICE:
                        // add payment information to the invoice pdf
                        if ($paymentMethod->renderInvoiceData()) {
                            /** @var OrderPdfGeneration $orderPdfGeneration */
                            $orderPdfGeneration           = pluginApp(OrderPdfGeneration::class);
                            $language                     = $modelHelper->getLanguage($order);
                            $orderPdfGeneration->language = $language;
                            $orderPdfGeneration->advice   = $paymentInfoService->getPaymentInformationString($order, $language);
                            $event->addOrderPdfGeneration($orderPdfGeneration);
                        }
                    break;
                    case Document::DELIVERY_NOTE:
                        // perform finalize transaction
                        if ($paymentMethod->sendFinalizeTransaction()) {
                            $paymentService->handleShipment($event->getOrder());
                        }
                        break;
                    default:
                        // do nothing
                        break;
                }
            }
        );
    }
}
