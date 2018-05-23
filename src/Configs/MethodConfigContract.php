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
    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getChannelIdKey(string $paymentMethod): string;

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getDisplayNameKey(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the config key for the iframe css url.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIFrameCssPathKey(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getDescriptionTypeKey(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIsActiveKey(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the minimum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMinAmountKey(PaymentMethodContract $paymentMethod): string;

    /**
     * Returns the maximum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMaxAmountKey(PaymentMethodContract $paymentMethod): string;

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
     * Returns the Methods description text.
     *
     * @param $paymentMethod
     * @return mixed|string
     */
    public function getMethodDescription($paymentMethod);

    /**
     * Returns the path of the payment method icon.
     *
     * @param $paymentMethod
     * @return string
     */
    public function getMethodIcon($paymentMethod): string;

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey(string $paymentMethod): string;

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName(string $paymentMethod): string;
}
