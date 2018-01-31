<?php

namespace Heidelpay\Helper;

use Heidelpay\Constants\ConfigKeys;
use Heidelpay\Constants\Plugin;
use Heidelpay\Methods\AbstractPaymentMethod;
use Heidelpay\Methods\CreditCardPaymentMethod;
use Heidelpay\Methods\PayPalPaymentMethod;
use Heidelpay\Methods\PrepaymentPaymentMethod;
use Heidelpay\Methods\SofortPaymentMethod;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Log\Loggable;

/**
 * Heidelpay Helper Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\helper
 */
class HeidelpayHelper
{
    use Loggable;

    const ARRAY_KEY_CONFIG_KEY = 'config_key';
    const ARRAY_KEY_DEFAULT_NAME = 'default_name';
    const ARRAY_KEY_KEY = 'key';

    const NO_CONFIG_KEY_FOUND = 'no_config_key_found';
    const NO_DEFAULT_NAME_FOUND = 'no_default_name_found';
    const NO_KEY_FOUND = 'no_key_found';

    const NO_PAYMENTMETHOD_FOUND = 'no_paymentmethod_found';

    /**
     * @var PaymentMethodRepositoryContract
     */
    protected $paymentMethodRepository;

    /**
     * @var array
     */
    public static $paymentMethods = [
        CreditCardPaymentMethod::class => [
            self::ARRAY_KEY_CONFIG_KEY => CreditCardPaymentMethod::CONFIG_KEY,
            self::ARRAY_KEY_KEY => CreditCardPaymentMethod::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => CreditCardPaymentMethod::DEFAULT_NAME,
        ],
        PrepaymentPaymentMethod::class => [
            self::ARRAY_KEY_CONFIG_KEY => PrepaymentPaymentMethod::CONFIG_KEY,
            self::ARRAY_KEY_KEY => PrepaymentPaymentMethod::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => PrepaymentPaymentMethod::DEFAULT_NAME,
        ],
        SofortPaymentMethod::class => [
            self::ARRAY_KEY_CONFIG_KEY => SofortPaymentMethod::CONFIG_KEY,
            self::ARRAY_KEY_KEY => SofortPaymentMethod::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => SofortPaymentMethod::DEFAULT_NAME,
        ],
        PayPalPaymentMethod::class => [
            self::ARRAY_KEY_CONFIG_KEY => PayPalPaymentMethod::CONFIG_KEY,
            self::ARRAY_KEY_KEY => PayPalPaymentMethod::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => PayPalPaymentMethod::DEFAULT_NAME,
        ],
    ];

    /**
     * AbstractHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * Create the payment method ID if it doesn't exist yet
     *
     * @param string $paymentMethodClass
     */
    public function createMopIfNotExists(string $paymentMethodClass)
    {
        if ($this->getPaymentMethodId($paymentMethodClass) === self::NO_PAYMENTMETHOD_FOUND) {
            $this->getLogger(__METHOD__)->info('Heidelpay::service_provider.method_not_found', [
                'paymentMethod' => $this->getPaymentMethodDefaultName($paymentMethodClass)
            ]);

            $paymentMethodData = [
                'pluginKey' => Plugin::KEY,
                'paymentKey' => $this->getPaymentMethodKey($paymentMethodClass),
                'name' => $this->getPaymentMethodDefaultName($paymentMethodClass)
            ];

            $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
        }
    }

    /**
     * Load the payment method ID for the given plugin key.
     *
     * @param string $paymentMethodClass
     *
     * @return string
     */
    public function getPaymentMethodId(string $paymentMethodClass): string
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(Plugin::KEY);

        if (!empty($paymentMethods)) {
            /** @var PaymentMethod $payMethod */
            foreach ($paymentMethods as $payMethod) {
                if ($payMethod->paymentKey === $this->getPaymentMethodKey($paymentMethodClass)) {
                    return $payMethod->id;
                }
            }
        }

        return self::NO_PAYMENTMETHOD_FOUND;
    }

    /**
     * Returns the event list when changes should be considered.
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

    /**
     * Returns the payment method key ('plugin_name::payment_key')
     *
     * @param string $paymentMethodClass
     *
     * @return string
     */
    public function getPluginPaymentMethodKey(string $paymentMethodClass): string
    {
        return Plugin::KEY . '::' . $this->getPaymentMethodKey($paymentMethodClass);
    }

    /**
     * Returns the complete config key (plugin name + config key) for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getConfigKey(string $key): string
    {
        return Plugin::NAME . '.' . $key;
    }

    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getChannelIdKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::CHANNEL_ID);
    }

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getDisplayNameKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DISPLAY_NAME);
    }

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getDescriptionTypeKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_TYPE);
    }

    /**
     * Returns the config key for the 'description/info page' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     * @param bool                  $isInternal
     *
     * @return string
     */
    public function getDescriptionKey(AbstractPaymentMethod $paymentMethod, bool $isInternal = false): string
    {
        if (!$isInternal) {
            return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_EXTERNAL);
        }

        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_INTERNAL);
    }

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getIsActiveKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::IS_ACTIVE);
    }

    /**
     * Returns the minimum cart total amount for the given Payment method.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getMinAmountKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MIN_AMOUNT);
    }

    /**
     * Returns the maximum cart total amount for the given Payment method.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getMaxAmountKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MAX_AMOUNT);
    }

    /**
     * Returns the payment method config key for the 'use logo' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getUseIconKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_USE);
    }

    /**
     * Returns the payment method config key for the 'logo url' configuration.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return string
     */
    public function getIconUrlKey(AbstractPaymentMethod $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_URL);
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodConfigKey(string $paymentMethod): string
    {
        return static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_CONFIG_KEY]
            ?? self::NO_CONFIG_KEY_FOUND;
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName(string $paymentMethod): string
    {
        $prefix = Plugin::NAME . ' - ';
        $name = static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_DEFAULT_NAME]
            ?? self::NO_DEFAULT_NAME_FOUND;

        return $prefix . $name;
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey(string $paymentMethod): string
    {
        return static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_KEY]
            ?? self::NO_KEY_FOUND;
    }

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
     * Gets a certain key from a given payment method in the helper string array.
     *
     * @param string $paymentMethodClass
     * @param string $key
     *
     * @return string
     */
    public function getPaymentMethodString(string $paymentMethodClass, string $key): string
    {
        return static::$paymentMethods[$paymentMethodClass][$key] ?? null;
    }
}
