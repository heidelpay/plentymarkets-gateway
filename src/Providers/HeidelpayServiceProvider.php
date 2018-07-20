<?php

namespace Heidelpay\Providers;

use Heidelpay\Configs\MainConfig;
use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfig;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Repositories\OrderTxnIdRelationRepository;
use Heidelpay\Models\Repositories\TransactionRepository;
use Heidelpay\Services\NotificationService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
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
 * @link http://dev.heidelpay.com/plentymarkets-gateway
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
    }

    /**
     * Boot the heidelpay Service Provider
     * Register payment methods, add event listeners, ...
     *
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentHelper            $paymentHelper
     * @param PaymentMethodContainer   $methodContainer
     * @param PaymentService           $paymentService
     * @param Dispatcher               $eventDispatcher
     */
    public function boot(
        BasketRepositoryContract $basketRepository,
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $methodContainer,
        PaymentService $paymentService,
        Dispatcher $eventDispatcher
    ) {
        $paymentHelper->createMopsIfNotExists();

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
                $basketRepository,
                $paymentHelper,
                $paymentService
            ) {
                $mop = $event->getMop();
                $paymentMethod = $paymentHelper->mapMopToPaymentMethod($mop);

                if (!empty($paymentMethod)) {
                    $basket = $basketRepository->load();
                    list($type, $value) = $paymentService->getPaymentMethodContent($paymentMethod, $basket, $mop);

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
    }
}
