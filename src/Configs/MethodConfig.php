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
namespace Heidelpay\Helper;

use Heidelpay\Constants\ConfigKeys;
use Heidelpay\Methods\PaymentMethodContract;

class MethodConfig extends BaseConfig implements MethodConfigContract
{
    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getChannelIdKey(string $paymentMethod): string
    {
        $paymentMethodKey = PaymentHelper::$paymentMethods[$paymentMethod][PaymentHelper::ARRAY_KEY_CONFIG_KEY];

        return $this->get($paymentMethodKey . '.' . ConfigKeys::CHANNEL_ID);
    }

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getDisplayNameKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($paymentMethod->getConfigKey() . '.' . ConfigKeys::DISPLAY_NAME);
    }

    /**
     * Returns the config key for the iframe css url.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIFrameCssPathKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($paymentMethod->getConfigKey() . '.' . ConfigKeys::IFRAME_CSS_URL);
    }

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getDescriptionTypeKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_TYPE);
    }

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIsActiveKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::IS_ACTIVE);
    }

    /**
     * Returns the minimum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMinAmountKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MIN_AMOUNT);
    }

    /**
     * Returns the maximum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMaxAmountKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MAX_AMOUNT);
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getTransactionChannel(string $paymentMethod): string
    {
        return $this->get($this->getChannelIdKey($paymentMethod));
    }

    /**
     * Returns if a payment method is enabled or disabled.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return bool
     */
    public function isActive(PaymentMethodContract $paymentMethod): bool
    {
        return $this->get($this->getIsActiveKey($paymentMethod)) === 'true';
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodName(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($this->getDisplayNameKey($paymentMethod));
    }

    /**
     * Returns the configured minimum amount for the given payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return float
     */
    public function getMinAmount(PaymentMethodContract $paymentMethod): float
    {
        return $this->stringToFloat($this->getMinAmountKey($paymentMethod));
    }

    /**
     * Returns the configured minimum amount for the given payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return float
     */
    public function getMaxAmount(PaymentMethodContract $paymentMethod): float
    {
        return $this->stringToFloat($this->getMaxAmountKey($paymentMethod));
    }

    /**
     * Returns the iFrame Css Path for the payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getIFrameCssPath(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($this->getIFrameCssPathKey($paymentMethod));
    }
}
