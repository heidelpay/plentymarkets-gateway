<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\ServiceProvider;

/**
 * heidelpay Service Provider
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\providers
 */
class HeidelpayServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
     * Register the heidelpay Service Providers.
     */
    public function register()
    {
        $this->getApplication()->register(HeidelpayRouteServiceProvider::class);
    }

    /**
     * Boot the heidelpay Service Prodiver
     * Register payment methods, add event listeners, ...
     *
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentHelper            $paymentHelper
     * @param PaymentMethodContainer   $paymentMethodContainer
     * @param PaymentService           $paymentService
     * @param Dispatcher               $eventDispatcher
     */
    public function boot(
        BasketRepositoryContract $basketRepository,
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $paymentMethodContainer,
        PaymentService $paymentService,
        Dispatcher $eventDispatcher
    ) {
        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethodClass */
        foreach ($paymentHelper::getPaymentMethods() as $paymentMethodClass) {
            $paymentHelper->createMopIfNotExists($paymentMethodClass);

            // register the payment method in the payment method container
            $paymentMethodContainer->register(
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
                if ($event->getMop() === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $basket = $basketRepository->load();
                    $event->setValue($paymentService->getPaymentMethodContent(PayPal::class, $basket));
                    $event->setType($paymentService->getReturnType());
                }

                if ($event->getMop() === $paymentHelper->getPaymentMethodId(CreditCard::class)) {
                    $basket = $basketRepository->load();
                    $event->setValue($paymentService->getPaymentMethodContent(CreditCard::class, $basket));
                    $event->setType($paymentService->getReturnType());
                }
            }
        );

        // listen for the event that executes the payment
        $eventDispatcher->listen(
            ExecutePayment::class,
            function (ExecutePayment $event) use (
                $basketRepository,
                $paymentHelper,
                $paymentService
            ) {
                if ($event->getMop() === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $basket = $basketRepository->load();

                    $event->setValue($paymentService->executePayment(PayPal::class, $basket));
                    $event->setType($paymentService->getReturnType());
                }
            }
        );
    }
}
