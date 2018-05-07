<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Repositories\TransactionRepository;
use Heidelpay\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
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
        $this->getApplication()->bind(TransactionRepositoryContract::class, TransactionRepository::class);
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
                $mop = $event->getMop();
                $basket = $basketRepository->load();
                $this->getLogger(__METHOD__)->error('Basket in GetPaymentMethodContent', [
                    $basket,
                    $event->getMop(),
                    $event->getParams(),
                    $event->getType(),
                    $event->getValue()
                ]);

                if ($mop === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $event->setValue($paymentService->getPaymentMethodContent(PayPal::class, $basket, $mop));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(CreditCard::class)) {
                    $event->setValue($paymentService->getPaymentMethodContent(CreditCard::class, $basket, $mop));
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
                $basket = $basketRepository->load();
                $mop = $event->getMop();
                if ($mop === $paymentHelper->getPaymentMethodId(CreditCard::class)) {
                    $event->setValue($paymentService->executePayment(CreditCard::class, $basket, $event));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $event->setValue($paymentService->executePayment(PayPal::class, $basket, $event));
                    $event->setType($paymentService->getReturnType());
                }
            }
        );

        $eventDispatcher->listen(
            AfterBasketChanged::class,
            function (AfterBasketChanged $event) use ($basketRepository) {
                $basket = $basketRepository->load();
                $this->getLogger(__METHOD__)->error(
                    'Basket changed', [
                        $basket, $event->getBasket(),
                        $event->getInvoiceAddress(),
                        $event->getLocationId(),
                        $event->getMaxFsk(),
                        $event->getShippingCosts()
                    ]
                );
            }
        );

        $eventDispatcher->listen(
            AfterBasketCreate::class,
            function (AfterBasketCreate $event) use ($basketRepository) {
                $basket = $basketRepository->load();
                $this->getLogger(__METHOD__)->error('Basket created', [$basket, $event->getBasket()]);
            }
        );
    }
}
