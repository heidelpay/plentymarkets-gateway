<?php
namespace Heidelpay\Configs;
use Heidelpay\Constants\Configuration;
use Heidelpay\Constants\DescriptionTypes;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\DirectDebit;
use Heidelpay\Methods\InvoiceSecuredB2C;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Methods\Sofort;

/**
 * Config class to access payment method configuration parameters.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\configuration
 */
class MethodConfig extends BaseConfig implements MethodConfigContract
{
    const ARRAY_KEY_CONFIG_KEY = 'config_key';
    const ARRAY_KEY_DEFAULT_NAME = 'default_name';
    const ARRAY_KEY_KEY = 'key';

    const NO_CONFIG_KEY_FOUND = 'no_config_key_found';
    const NO_DEFAULT_NAME_FOUND = 'no_default_name_found';
    const NO_KEY_FOUND = 'no_key_found';

    //<editor-fold desc="General/Helpers">
    /**
     * @var array
     */
    public static $paymentMethods = [
        CreditCard::class => [
            self::ARRAY_KEY_CONFIG_KEY => CreditCard::CONFIG_KEY,
            self::ARRAY_KEY_KEY => CreditCard::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => CreditCard::DEFAULT_NAME
        ],
        DebitCard::class => [
            self::ARRAY_KEY_CONFIG_KEY => DebitCard::CONFIG_KEY,
            self::ARRAY_KEY_KEY => DebitCard::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => DebitCard::DEFAULT_NAME
        ],
        Sofort::class => [
            self::ARRAY_KEY_CONFIG_KEY => Sofort::CONFIG_KEY,
            self::ARRAY_KEY_KEY => Sofort::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => Sofort::DEFAULT_NAME
        ],
        DirectDebit::class => [
            self::ARRAY_KEY_CONFIG_KEY => DirectDebit::CONFIG_KEY,
            self::ARRAY_KEY_KEY => DirectDebit::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => DirectDebit::DEFAULT_NAME
        ],
        InvoiceSecuredB2C::class => [
            self::ARRAY_KEY_CONFIG_KEY => InvoiceSecuredB2C::CONFIG_KEY,
            self::ARRAY_KEY_KEY => InvoiceSecuredB2C::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => InvoiceSecuredB2C::DEFAULT_NAME
        ]
    ];

    /**
     * Returns the available payment methods and their helper strings (config-key, payment-key, default name).
     *
     * @return string[]
     */
    public static function getPaymentMethods(): array
    {
        return array_keys(static::$paymentMethods);
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getMethodDescriptionType(PaymentMethodContract $paymentMethod): string
    {
        return $this->get($this->getDescriptionTypeKey($paymentMethod)) ?? DescriptionTypes::NONE;
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName(string $paymentMethod): string
    {
        $prefix = Plugin::NAME . ' - ';
        $name = static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_DEFAULT_NAME] ?? self::NO_DEFAULT_NAME_FOUND;

        return $prefix . $name;
    }
    //</editor-fold>

    //<editor-fold desc="Getters for Payment parameters">
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
        $name = $this->get($this->getDisplayNameKey($paymentMethod));

        if ($name === null) {
            return 'Error loading payment method: ' . \get_class($paymentMethod);
        }

        return $name;
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
        return $this->stringToFloat($this->get($this->getMinAmountKey($paymentMethod)));
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
        return $this->stringToFloat($this->get($this->getMaxAmountKey($paymentMethod)));
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
        $stylePath = $this->get($this->getIFrameCssPathKey($paymentMethod));
        return $this->returnVerifiedUrlToFile($stylePath, ['css']);
    }

    /**
     * Returns the configured booking mode as string.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return string
     */
    public function getTransactionType(PaymentMethodContract $paymentMethod): string
    {
        return $this->hasBookingModeDebit($paymentMethod) ? TransactionType::DEBIT : TransactionType::AUTHORIZE;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(PaymentMethodContract $paymentMethod): string
    {
        $iconPath = $this->get($this->getIconPathKey($paymentMethod));
        return $this->returnVerifiedUrlToFile($iconPath, ['gif', 'jpg', 'png']);
    }

    /**
     * {@inheritDoc}
     */
    public function hasTransactionType(PaymentMethodContract $paymentMethod): bool
    {
        $hasTransactionType = $this->has($this->getBookingModeKey($paymentMethod));
        return $hasTransactionType && !empty($this->getTransactionType($paymentMethod));
    }

    /**
     * Returns true if the configured booking mode is debit.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return bool
     */
    public function hasBookingModeDebit(PaymentMethodContract $paymentMethod): bool
    {
        $mode = (int)$this->get($this->getBookingModeKey($paymentMethod));
        return $mode === Configuration::VALUE_BOOKING_MODE_DEBIT;
    }

    /**
     * Returns true if the configured booking mode is registration.
     *
     * @param PaymentMethodContract $paymentMethod
     * @return bool
     */
    public function hasBookingModeRegistration(PaymentMethodContract $paymentMethod): bool
    {
        return !$this->hasBookingModeDebit($paymentMethod);
    }
    //</editor-fold>

    //<editor-fold desc="Getters for Plenty payment parameters">
    /**
     * Returns the Methods description text.
     *
     * @param $paymentMethod
     * @return mixed|string
     */
    public function getMethodDescription($paymentMethod)
    {
        $type = $this->getMethodDescriptionType($paymentMethod);

        switch ($type) {
            case DescriptionTypes::INTERNAL:
                $methodDescription = $this->get($this->getDescriptionKey($paymentMethod, true));
                break;
            case DescriptionTypes::EXTERNAL:
                $methodDescription = $this->get($this->getDescriptionKey($paymentMethod));
                break;
            default:
                $methodDescription = '';
                break;
        }

        return $methodDescription;
    }
    //</editor-fold>

    //<editor-fold desc="Getters for config keys">
    /**
     * This is also used within the PaymentHelper class, so it must be public.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey(string $paymentMethod): string
    {
        return static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_KEY] ?? self::NO_KEY_FOUND;
    }

    /**
     * Returns the config key for the 'description/info page' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     * @param bool           $isInternal
     *
     * @return string
     */
    protected function getDescriptionKey(PaymentMethodContract $paymentMethod, bool $isInternal = false): string
    {
        if (!$isInternal) {
            return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_DESCRIPTION_EXTERNAL);
        }

        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_DESCRIPTION_INTERNAL);
    }

    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    protected function getChannelIdKey(string $paymentMethod): string
    {
        $paymentMethodKey = self::$paymentMethods[$paymentMethod][self::ARRAY_KEY_CONFIG_KEY];

        return $this->getConfigKey($paymentMethodKey . '.' . Configuration::KEY_CHANNEL_ID);
    }

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getDisplayNameKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_DISPLAY_NAME);
    }

    /**
     * Returns the config key for the iframe css url.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getIFrameCssPathKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_IFRAME_CSS_URL);
    }

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getDescriptionTypeKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_DESCRIPTION_TYPE);
    }

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getIsActiveKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_IS_ACTIVE);
    }

    /**
     * Returns the minimum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getMinAmountKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_MIN_AMOUNT);
    }

    /**
     * Returns the maximum cart total amount for the given Payment method.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getMaxAmountKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_MAX_AMOUNT);
    }

    /**
     * Returns the key of the booking mode parameter.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getBookingModeKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_BOOKING_MODE);
    }

    /**
     * Returns the key of the icon path parameter.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getIconPathKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . Configuration::KEY_ICON_PATH);
    }

    /**
     * @param string $path
     * @param array $allowedExtensions
     * @return string
     */
    protected function returnVerifiedUrlToFile($path, array $allowedExtensions): string
    {
        $returnPath = $path;
        $preg = '#^(http|https)://.*\.(' . implode('|', $allowedExtensions) . ')#i';
        if (!preg_match($preg, $path)) {
            $returnPath = '';
        }
        return $returnPath;
    }
    //</editor-fold>
}
