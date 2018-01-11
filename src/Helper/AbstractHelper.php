<?php

namespace Heidelpay\Helper;

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
    const CONFIG_KEY_IS_ACTIVE = 'isActive';
    const CONFIG_KEY_DISPLAY_NAME = 'displayName';
    const CONFIG_KEY_DESCRIPTION_TYPE = 'infoPage.type';
    const CONFIG_KEY_DESCRIPTION_INTERNAL = 'infoPage.intern';
    const CONFIG_KEY_DESCRIPTION_EXTERNAL = 'infoPage.extern';
    const CONFIG_KEY_LOGO_USE = 'logo.use';
    const CONFIG_KEY_LOGO_URL = 'logo.url';
    const CONFIG_KEY_CHANNEL_ID = 'channelId';
    const CONFIG_KEY_DO_REGISTRATION = 'doRegistration';
    const CONFIG_KEY_IFRAME_CSS_URL = 'iframeCss';

    const DESCRIPTION_TYPE_NONE = '0';
    const DESCRIPTION_TYPE_INTERNAL = '1';
    const DESCRIPTION_TYPE_EXTERNAL = '2';

    const PLUGIN_KEY = 'heidelpay_gateway';
    const PLUGIN_NAME = 'heidelpayPaymentGateway';

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
                'pluginKey' => self::PLUGIN_KEY,
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
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(self::PLUGIN_KEY);

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
     * Returns the payment method key ('plugin_name::payment_key')
     *
     * @return string
     */
    final public function getPluginPaymentMethodKey(): string
    {
        return self::PLUGIN_KEY . '::' . $this->getPaymentKey();
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
        return self::PLUGIN_NAME . '.' . $key;
    }

    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @return string
     */
    final public function getChannelIdKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_CHANNEL_ID);
    }

    /**
     * Returns the payment method config key for the 'Display name' configuration.
     *
     * @return string
     */
    final public function getDisplayNameKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_DISPLAY_NAME);
    }

    /**
     * Returns the payment method config key for the 'description/info page type' configuration.
     *
     * @return string
     */
    final public function getDescriptionTypeKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_DESCRIPTION_TYPE);
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
            return $this->getConfigKey(
                $this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_DESCRIPTION_EXTERNAL
            );
        }

        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_DESCRIPTION_INTERNAL);
    }

    /**
     * Returns the payment method config key for the 'is active' configuration.
     *
     * @return string
     */
    final public function getIsActiveKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_IS_ACTIVE);
    }

    /**
     * Returns the payment method config key for the 'use logo' configuration.
     *
     * @return string
     */
    final public function getUseIconKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_LOGO_USE);
    }

    /**
     * Returns the payment method config key for the 'logo url' configuration.
     *
     * @return string
     */
    final public function getIconUrlKey(): string
    {
        return $this->getConfigKey($this->getPaymentMethodConfigKey() . '.' . self::CONFIG_KEY_LOGO_URL);
    }
}
