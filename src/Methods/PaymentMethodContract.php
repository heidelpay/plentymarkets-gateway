<?php

namespace Heidelpay\Methods;

/**
 * heidelpay Payment Method Interface
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
interface PaymentMethodContract
{
    /**
     * Returns if the payment method is active and can be used by the customer.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Returns if the payment method can be used for Express Checkout.
     *
     * @return bool
     */
    public function isExpressCheckout(): bool;

    /**
     * Returns if the payment method can be selected.
     *
     * @return bool
     */
    public function isSelectable(): bool;

    /**
     * Determines if the customer can switch from this payment method
     * in his 'My account' area after an order has been placed.
     *
     * @return bool
     */
    public function isSwitchableFrom(): bool;

    /**
     * Determines if the customer can switch to this payment method
     * in his 'My account' area after an order has been placed.
     *
     * @return bool
     */
    public function isSwitchableTo(): bool;

    /**
     * Returns a fee amount for this payment method.
     *
     * @return float
     */
    public function getFee(): float;

    /**
     * Returns the config key for the payment method.
     *
     * @return string
     */
    public static function getConfigKey(): string;

    /**
     * Returns a default display name for the payment method.
     *
     * @return string
     */
    public function getDefaultName(): string;

    /**
     * Returns the configured payment method description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns the configured icon logo, if logo usage is enabled for this payment method.
     *
     * @return string
     */
    public function getIcon(): string;

    /**
     * Returns the configured payment method display name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the default display name for the payment method (static).
     *
     * @return string
     */
    public static function getPaymentMethodDefaultName(): string;

    /**
     * Returns the php-payment-api transaction method to be called.
     *
     * @return string
     */
    public function getTransactionType(): string;

    /**
     * Returns true if the payment has to be initialized with transaction (i.e. to fetch redirect url).
     *
     * @return bool
     */
    public function hasToBeInitialized(): bool;

    /**
     * Returns the template of the payment form.
     *
     * @return string
     */
    public function getFormTemplate(): string;

    /**
     * Returns true if the customer has to be redirected to enter additional information (e.g. 3D-secure, sofort, etc.).
     * This determines whether a synchronous or asynchronous request is performed.
     *
     * @return bool
     */
    public function needsCustomerInput(): bool;

    /**
     * Returns true if a basket id has to be requested.
     *
     * @return bool
     */
    public function needsBasket(): bool;
}
