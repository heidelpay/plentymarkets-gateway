<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\AbstractHelper;
use Heidelpay\Helper\CreditCardHelper;
use Heidelpay\Helper\PrepaymentHelper;
use Heidelpay\Methods\CreditCardPaymentMethod;
use Heidelpay\Methods\PrepaymentPaymentMethod;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Plugin\Events\Dispatcher;
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
    /**
     * Register the heidelpay Service Providers.
     */
    public function register()
    {
    }

    public function boot(
        PaymentMethodContainer $paymentMethodContainer,
        Dispatcher $eventDispatcher
    ) {
        // loop through all of the plugin's available payment methods
        foreach ($this->getPaymentMethods() as $paymentMethod => $paymentMethodHelper) {
            /** @var AbstractHelper $helperInstance */
            $helperInstance = new $paymentMethodHelper;

            // create a mop (payment method id) if it does not exist
            $helperInstance->createMopIfNotExists();

            // register the payment method in the payment method container
            $paymentMethodContainer->register(
                $helperInstance->getPluginPaymentMethodKey(),
                $paymentMethod,
                $helperInstance->getPaymentMethodEventList()
            );

            // Listen for the event that gets the payment method content
            $eventDispatcher->listen(
                GetPaymentMethodContent::class,
                function (GetPaymentMethodContent $event) use ($helperInstance) {
                    if ($event->getMop() === $helperInstance->getPaymentMethodId()) {
                        $event->setValue('');
                        $event->setType('continue');
                    }
                }
            );

            // listen for the event that executes the payment
            $eventDispatcher->listen(
                ExecutePayment::class,
                function (ExecutePayment $event) use ($helperInstance) {
                    if ($event->getMop() === $helperInstance->getPaymentMethodId()) {
                        $event->setValue('<h1>' . $helperInstance->getPaymentMethodDefaultName() . '</h1>');
                        $event->setType('htmlContent');
                    }
                }
            );
        }
    }

    private function getPaymentMethods()
    {
        return [
            CreditCardPaymentMethod::class => CreditCardHelper::class,
            PrepaymentPaymentMethod::class => PrepaymentHelper::class,
        ];
    }
}
