<?php

namespace Heidelpay\Providers;

use Heidelpay\Helper\PrepaymentHelper;
use Heidelpay\Methods\PrepaymentPaymentMethod;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;

/**
 * Prepayment Service Provider
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\service-providers
 */
class PrepaymentServiceProvider extends ServiceProvider
{
    /**
     * Register the heidelpay Service Providers.
     */
    public function register()
    {
    }

    public function boot(
        PrepaymentHelper $paymentHelper,
        PaymentMethodContainer $paymentMethodContainer,
        Dispatcher $eventDispatcher
    ) {
        // create a mop (payment method id) if it does not exist.
        $paymentHelper->createMopIfNotExists();

        // register the payment method in the payment method container
        $paymentMethodContainer->register(
            $paymentHelper->getPluginPaymentMethodKey(),
            PrepaymentPaymentMethod::class,
            [AfterBasketChanged::class, AfterBasketItemAdd::class, AfterBasketCreate::class]
        );

        // listen for the event that gets the payment method content
        $eventDispatcher->listen(
            GetPaymentMethodContent::class,
            function (GetPaymentMethodContent $event) use ($paymentHelper) {
                if ($event->getMop() === $paymentHelper->getPaymentMethodId()) {
                    $event->setValue('');
                    $event->setType('continue');
                }
            }
        );

        // listen for the event that executes the payment
        $eventDispatcher->listen(
            ExecutePayment::class,
            function (ExecutePayment $event) use ($paymentHelper) {
                if ($event->getMop() === $paymentHelper->getPaymentMethodId()) {
                    $event->setValue('<h1>' . $paymentHelper->getPaymentMethodDefaultName() . '</h1>');
                    $event->setType('htmlContent');
                }
            }
        );
    }
}
