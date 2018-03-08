<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PayPalPaymentMethod;
use Heidelpay\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
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

    public function boot(
        BasketRepositoryContract $basketRepository,
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $paymentMethodContainer,
        PaymentService $paymentService,
        Dispatcher $eventDispatcher
    ) {
        $this->getLogger(__METHOD__)->error('Heidelpay::serviceprovider.booting');

        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethodClass */
        foreach ($paymentHelper::getPaymentMethods() as $paymentMethodClass) {
            $paymentHelper->createMopIfNotExists($paymentMethodClass);

            $this->getLogger(__METHOD__)->error('Heidelpay::serviceprovider.registerMethod', [
                'paymentMethod' => $paymentHelper->getPaymentMethodDefaultName($paymentMethodClass)
            ]);

            // register the payment method in the payment method container
            $paymentMethodContainer->register(
                $paymentHelper->getPluginPaymentMethodKey($paymentMethodClass),
                $paymentMethodClass,
                $this->getPaymentMethodEventList()
            );

            // listen for the event that gets the payment method content
            $eventDispatcher->listen(
                GetPaymentMethodContent::class,
                function (GetPaymentMethodContent $event) use (
                    $basketRepository,
                    $paymentHelper,
                    $paymentService,
                    $paymentMethodClass
                ) {
                    /*
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId($paymentMethodClass)) {
                        $event->setValue('');
                        $event->setType(GetPaymentMethodContent::RETURN_TYPE_CONTINUE);
                    }
                    */
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId(PayPalPaymentMethod::class)) {
                        $event->setType(GetPaymentMethodContent::RETURN_TYPE_CONTINUE)
                            ->setValue($paymentService->getPaymentMethodContent($paymentMethodClass));
                    }
                }
            );

            // listen for the event that executes the payment
            $eventDispatcher->listen(
                ExecutePayment::class,
                function (ExecutePayment $event) use (
                    $basketRepository,
                    $paymentHelper,
                    $paymentService,
                    $paymentMethodClass
                ) {
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId(PayPalPaymentMethod::class)) {
                        $basket = $basketRepository->load();

                        $event->setValue($paymentService->executePayment($basket, $paymentMethodClass));
                        $event->setType($paymentService->getReturnType());
                    }
                }
            );
        }
    }

    /**
     * Returns a list of events that should be observed.
     *
     * @return array
     */
    public function getPaymentMethodEventList(): array
    {
        return [
            AfterBasketChanged::class,
            AfterBasketItemAdd::class,
            AfterBasketCreate::class,
            FrontendLanguageChanged::class,
            FrontendShippingCountryChanged::class,
        ];
    }
}
