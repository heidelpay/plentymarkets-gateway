<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\HeidelpayHelper;
use Heidelpay\Methods\AbstractPaymentMethod;
use Heidelpay\Methods\CreditCardPaymentMethod;
use Heidelpay\Methods\PrepaymentPaymentMethod;
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
        // loop through all of the plugin's available payment methods
        /** @var string $paymentMethod */
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            /** @var AbstractPaymentMethod $methodInstance */
            $methodInstance = pluginApp($paymentMethod);

            if ($methodInstance === null) {
                $this->getLogger(__METHOD__)
                    ->error('Heidelpay::service_provider.method_register_failed', [$paymentMethod]);
            }

            // create a mop (payment method id) if it does not exist
            $paymentHelper->createMopIfNotExists($methodInstance);

            // register the payment method in the payment method container
            $paymentMethodContainer->register(
                $paymentHelper->getPluginPaymentMethodKey($methodInstance),
                $paymentMethod,
                $paymentHelper->getPaymentMethodEventList()
            );

            // listen for the event that gets the payment method content
            $eventDispatcher->listen(
                GetPaymentMethodContent::class,
                function (GetPaymentMethodContent $event) use ($paymentHelper, $methodInstance) {
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId($methodInstance)) {
                        $event->setValue('');
                        $event->setType('continue');
                    }
                }
            );

            // listen for the event that executes the payment
            $eventDispatcher->listen(
                ExecutePayment::class,
                function (ExecutePayment $event) use ($paymentHelper, $methodInstance) {
                    if ($event->getMop() === $paymentHelper->getPaymentMethodId($methodInstance)) {
                        $event->setValue('<h1>' . $methodInstance->getDefaultName() . '</h1>');
                        $event->setType('htmlContent');
                    }
                }
            );
        }
    }

    /**
     * Returns the available payment methods for this plugin.
     *
     * @return array
     */
    private function getPaymentMethods(): array
    {
        return [
            CreditCardPaymentMethod::class,
            PrepaymentPaymentMethod::class,
        ];
    }
}
