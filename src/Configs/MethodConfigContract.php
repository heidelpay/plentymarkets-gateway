<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/${Package}
 */

namespace Heidelpay\Configs;

use Heidelpay\Methods\PaymentMethodContract;

interface MethodConfigContract
{
    //<editor-fold desc="General/Helpers">
    /**
     * Returns the available payment methods and their helper strings (config-key, payment-key, default name).
     *
     * @return string[]
     */
    public static function getPaymentMethods(): array;

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName(string $paymentMethod): string;
    //</editor-fold>

    //<editor-fold desc="Getters for Payment parameters">
    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getTransactionChannel(string $paymentMethod): string;

    /**
     * Returns if a payment method is enabled or disabled.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return bool
     */
    public function isActive(PaymentMethodContract $paymentMethod): bool;

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodName(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the configured minimum amount for the given payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return float
     */
    public function getMinAmount(PaymentMethodContract $paymentMethod): float;

    /**
     * Returns the configured minimum amount for the given payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return float
     */
    public function getMaxAmount(PaymentMethodContract $paymentMethod): float;

    /**
     * Returns the iFrame Css Path for the payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIFrameCssPath(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the configured booking mode as string.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return string
     */
    public function getTransactionType(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns true if the configured booking mode is debit.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return bool
     */
    public function hasBookingModeDebit(PaymentMethodContract $paymentMethod): bool;

    /**
     * Returns true if the configured booking mode is registration.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return bool
     */
    public function hasBookingModeRegistration(PaymentMethodContract $paymentMethod): bool;

    //</editor-fold>

    //<editor-fold desc="Getters for Plenty payment parameters">
    /**
     * Returns the Methods description text.
     *
     * @param $paymentMethod
     * @return mixed|string
     */
    public function getMethodDescription($paymentMethod);
    //</editor-fold>

    //<editor-fold desc="Public getters for config keys">
    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey(string $paymentMethod): string;

    /**
     * Returns true if the given parameter exists and is not empty.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return string
     */
    public function hasTransactionType(PaymentMethodContract $paymentMethod): string;
    //</editor-fold>
}
