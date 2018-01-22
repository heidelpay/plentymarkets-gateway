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

/**
 * Heidelpay Helper Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\helper
 */
class HeidelpayHelper
{
    const ARRAY_KEY_CONFIG_KEY = 'config_key';
    const ARRAY_KEY_DEFAULT_NAME = 'default_name';
    const ARRAY_KEY_KEY = 'key';

    const NO_PAYMENTMETHOD_FOUND = 'no_paymentmethod_found';

    /**
     * @var array
     */
    private $paymentMethodHelperStrings;

    /**
     * @var PaymentMethodRepositoryContract $paymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * AbstractHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->setPaymentMethodHelperStrings();
    }

    /**
     * Create the payment method ID if it doesn't exist yet
     *
     * @param string $paymentMethod
     */
    public function createMopIfNotExists($paymentMethod)
    {
        if ($this->getPaymentMethodId($paymentMethod) === self::NO_PAYMENTMETHOD_FOUND) {
            $paymentMethodData = [
                'pluginKey' => Plugin::KEY,
                'paymentKey' => $this->getPaymentMethodKey($paymentMethod),
                'name' => $this->getPaymentMethodDefaultName($paymentMethod)
            ];

            $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
        }
    }

    /**
     * Load the payment method ID for the given plugin key.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodId($paymentMethod): string
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(Plugin::KEY);

        if (!empty($paymentMethods)) {
            /** @var PaymentMethod $payMethod */
            foreach ($paymentMethods as $payMethod) {
                if ($payMethod->paymentKey === $this->getPaymentMethodKey($paymentMethod)) {
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
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPluginPaymentMethodKey($paymentMethod): string
    {
        return Plugin::KEY . '::' . $this->getPaymentMethodKey($paymentMethod);
    }

    /**
     * Returns the complete config key (plugin name + config key) for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getConfigKey($key): string
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
    public function getDescriptionKey(AbstractPaymentMethod $paymentMethod, $isInternal = false): string
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
    public function getPaymentMethodConfigKey($paymentMethod): string
    {
        return $this->paymentMethodHelperStrings[$paymentMethod][self::ARRAY_KEY_CONFIG_KEY] ?? null;
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName($paymentMethod): string
    {
        return $this->paymentMethodHelperStrings[$paymentMethod][self::ARRAY_KEY_DEFAULT_NAME] ?? null;
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey($paymentMethod): string
    {
        return $this->paymentMethodHelperStrings[$paymentMethod][self::ARRAY_KEY_KEY] ?? null;
    }

    /**
     * @return array
     */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethodHelperStrings;
    }

    /**
     * @param string $paymentMethodClass
     * @param string $key
     *
     * @return string
     */
    public function getPaymentMethodString($paymentMethodClass, $key): string
    {
        return $this->paymentMethodHelperStrings[$paymentMethodClass][$key] ?? null;
    }

    /**
     * Sets the available payment methods and their strings for this plugin.
     */
    private function setPaymentMethodHelperStrings()
    {
        $this->paymentMethodHelperStrings = [
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
    }
}
