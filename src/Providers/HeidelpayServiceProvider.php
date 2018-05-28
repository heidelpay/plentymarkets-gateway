<?php

namespace Heidelpay\Providers;

use Heidelpay\Configs\MainConfig;
use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfig;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Repositories\TransactionRepository;
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
        $this->getApplication()->bind(TransactionRepositoryContract::class, TransactionRepository::class);
        $this->getApplication()->bind(MainConfigContract::class, MainConfig::class);
        $this->getApplication()->bind(MethodConfigContract::class, MethodConfig::class);
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
        $paymentHelper->createMopIfNotExists($paymentMethodClass);

        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethodClass */
        foreach (MethodConfig::getPaymentMethods() as $paymentMethodClass) {
            $this->getLogger(__METHOD__)->debug('heidelpay:payment.debugRegisterPaymentMethod', [$paymentMethodClass]);

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

                if ($mop === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $event->setValue($paymentService->getPaymentMethodContent(PayPal::class, $basket, $mop));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(CreditCard::class)) {
                    $event->setValue($paymentService->getPaymentMethodContent(CreditCard::class, $basket, $mop));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(DebitCard::class)) {
                    $event->setValue($paymentService->getPaymentMethodContent(DebitCard::class, $basket, $mop));
                    $event->setType($paymentService->getReturnType());
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
                if ($mop === $paymentHelper->getPaymentMethodId(CreditCard::class)) {
                    $event->setValue($paymentService->executePayment(CreditCard::class, $event));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(DebitCard::class)) {
                    $event->setValue($paymentService->executePayment(DebitCard::class, $event));
                    $event->setType($paymentService->getReturnType());
                }

                if ($mop === $paymentHelper->getPaymentMethodId(PayPal::class)) {
                    $event->setValue($paymentService->executePayment(PayPal::class, $event));
                    $event->setType($paymentService->getReturnType());
                }
            }
        );
    }
}
