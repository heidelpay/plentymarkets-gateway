<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\HeidelpayHelper;
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
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
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
        HeidelpayHelper $paymentHelper,
        PaymentMethodContainer $paymentMethodContainer,
        Dispatcher $eventDispatcher
    ) {
        $this->getLogger(__METHOD__)->debug('Heidelpay::service_provider.booting');

        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethodClass */
        foreach ($paymentHelper::getPaymentMethods() as $paymentMethodClass) {
            $paymentHelper->createMopIfNotExists($paymentMethodClass);

            $this->getLogger(__METHOD__)->info('Heidelpay::service_provider.register_method', [
                'paymentMethod' => $paymentHelper->getPaymentMethodDefaultName($paymentMethodClass)
            ]);

            // register the payment method in the payment method container
            $paymentMethodContainer->register(
                $paymentHelper->getPluginPaymentMethodKey($paymentMethodClass),
                $paymentMethodClass,
                $paymentHelper->getPaymentMethodEventList()
            );

            // listen for the event that gets the payment method content
            $eventDispatcher->listen(
                GetPaymentMethodContent::class,
                function (GetPaymentMethodContent $event) use ($paymentHelper, $paymentMethodClass) {
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId($paymentMethodClass)) {
                        $event->setValue('');
                        $event->setType('continue');
                    }
                }
            );

            // listen for the event that executes the payment
            $eventDispatcher->listen(
                ExecutePayment::class,
                function (ExecutePayment $event) use ($paymentHelper, $paymentMethodClass) {
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId($paymentMethodClass)) {
                        $event->setValue(
                            '<h1>' . $paymentHelper->getPaymentMethodDefaultName($paymentMethodClass) . '</h1>'
                        );
                        $event->setType('htmlContent');
                    }
                }
            );
        }
    }
}
