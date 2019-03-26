<?php

namespace Heidelpay\Providers;

use Heidelpay\Configs\MainConfig;
use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfig;
use Heidelpay\Configs\MethodConfigContract;
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
use Heidelpay\Services\PaymentService;
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
    }

    /**
     * Boot the heidelpay Service Provider
     * Register payment methods, add event listeners, ...
     *
     * @param PaymentHelper $paymentHelper
     * @param PaymentMethodContainer $methodContainer
     * @param PaymentService $paymentService
     * @param Dispatcher $eventDispatcher
     * @param NotificationServiceContract $notificationService
     * @param OrderServiceContract $orderService
     */
    public function boot(
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $methodContainer,
        PaymentService $paymentService,
        Dispatcher $eventDispatcher,
        NotificationServiceContract $notificationService,
        OrderServiceContract $orderService
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
            function (GetPaymentMethodContent $event) use (
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
            function (ExecutePayment $event) use (
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

        // add payment information to the invoice pdf
        $eventDispatcher->listen(
            OrderPdfGenerationEvent::class,
            function (OrderPdfGenerationEvent $event) use (
                $notificationService, $paymentHelper, $orderService
            ) {
                /** @var Order $order */
                $order = $event->getOrder();
                $docType = $event->getDocType();
                $mopId = $order->methodOfPaymentId;

                /** @var AbstractMethod $paymentMethod */
                $paymentMethod = $paymentHelper->getPaymentMethodInstanceByMopId($mopId);

                if ($docType !== Document::INVOICE
                    || !$paymentMethod instanceof AbstractMethod
                    || !$paymentMethod->renderInvoiceData()) {
                    // do nothing if invoice data does not need to be rendered
                    return;
                }

                /** @var OrderPdfGeneration $orderPdfGeneration */
                $orderPdfGeneration           = pluginApp(OrderPdfGeneration::class);
                $language                     = $orderService->getLanguage($order);
                $orderPdfGeneration->language = $language;

                $paymentDetails = $paymentHelper->getPaymentDetailsForOrder($order);

                $adviceParts = [
                    $notificationService->getTranslation('Heidelpay::template.pleaseTransferTheTotalTo', [], $language),
                    $notificationService->getTranslation('Heidelpay::template.accountIban', [], $language) . ': ' .
                        $paymentDetails['accountIBAN'],
                    $notificationService->getTranslation('Heidelpay::template.accountBic', [], $language) . ': ' .
                        $paymentDetails['accountBIC'],
                    $notificationService->getTranslation('Heidelpay::template.accountHolder', [], $language) . ': ' .
                        $paymentDetails['accountHolder'],
                    $notificationService->getTranslation('Heidelpay::template.accountUsage', [], $language) . ': ' .
                        $paymentDetails['accountUsage']
                ];
                $orderPdfGeneration->advice = implode(PHP_EOL, $adviceParts);

                $event->addOrderPdfGeneration($orderPdfGeneration);
            }
        );
    }
}
