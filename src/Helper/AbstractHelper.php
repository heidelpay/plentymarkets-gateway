<?php

namespace Heidelpay\Helper;

use Heidelpay\Constants\ConfigKeys;
use Heidelpay\Constants\Plugin;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

/**
 * Abstract Payment Method Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\helper
 */
abstract class AbstractHelper
{
    const NO_PAYMENTMETHOD_FOUND = 'no_paymentmethod_found';

    /**
     * @var PaymentMethodRepositoryContract $paymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * Returns the code for the payment method.
     *
     * @return string
     */
    abstract public function getPaymentKey(): string;

    /**
     * Returns the config key for the payment method.
     *
     * @return string
     */
    abstract public function getPaymentMethodConfigKey(): string;

    /**
     * Returns the name/label for the payment method.
     *
     * @return string
     */
    abstract public function getPaymentMethodDefaultName(): string;

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
     */
    final public function createMopIfNotExists()
    {
        if ($this->getPaymentMethodId() === self::NO_PAYMENTMETHOD_FOUND) {
            $paymentMethodData = [
                'pluginKey' => Plugin::KEY,
                'paymentKey' => $this->getPaymentKey(),
                'name' => $this->getPaymentMethodDefaultName()
            ];

            $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
        }
    }

    /**
     * Load the payment method ID for the given plugin key.
     *
     * @return string
     */
    final public function getPaymentMethodId(): string
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(Plugin::KEY);

        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod->paymentKey === $this->getPaymentKey()) {
                    return $paymentMethod->id;
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
     * @return string
     */
    final public function getPluginPaymentMethodKey(): string
    {
        return Plugin::KEY . '::' . $this->getPaymentKey();
    }

    /**
     * Returns the complete config key (plugin name + config key) for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    final public function getConfigKey($key): string
    {
        return Plugin::NAME . '.' . $key;
    }

    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @return string
     */
    final public function getChannelIdKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::CHANNEL_ID);
    }

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @return string
     */
    final public function getDisplayNameKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::DISPLAY_NAME);
    }

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @return string
     */
    final public function getDescriptionTypeKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::DESCRIPTION_TYPE);
    }

    /**
     * Returns the config key for the 'description/info page' configuration.
     *
     * @param bool $isInternal
     *
     * @return string
     */
    final public function getDescriptionKey($isInternal = false): string
    {
        if (!$isInternal) {
            return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::DESCRIPTION_EXTERNAL);
        }

        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::DESCRIPTION_INTERNAL);
    }

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @return string
     */
    final public function getIsActiveKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::IS_ACTIVE);
    }

    /**
     * Returns the payment method config key for the 'use logo' configuration.
     *
     * @return string
     */
    final public function getUseIconKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::LOGO_USE);
    }

    /**
     * Returns the payment method config key for the 'logo url' configuration.
     *
     * @return string
     */
    final public function getIconUrlKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . ConfigKeys::LOGO_URL);
    }
}
