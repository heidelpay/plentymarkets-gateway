<?php

namespace Heidelpay\Helper;

use Heidelpay\Constants\ConfigKeys;
use Heidelpay\Constants\DescriptionTypes;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\TransactionMode;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

/**
 * Heidelpay Payment Helper Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\helper
 */
class PaymentHelper
{
    use Loggable;

    const ARRAY_KEY_CONFIG_KEY = 'config_key';
    const ARRAY_KEY_DEFAULT_NAME = 'default_name';
    const ARRAY_KEY_KEY = 'key';

    const NO_CONFIG_KEY_FOUND = 'no_config_key_found';
    const NO_DEFAULT_NAME_FOUND = 'no_default_name_found';
    const NO_KEY_FOUND = 'no_key_found';

    const NO_PAYMENTMETHOD_FOUND = -1;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * @var PaymentMethodRepositoryContract
     */
    protected $paymentMethodRepository;

    /**
     * @var array
     */
    public static $paymentMethods = [
        CreditCard::class,
        Prepayment::class,
        Sofort::class,
        PayPal::class,
    ];

    /**
     * AbstractHelper constructor.
     *
     * @param ConfigRepository $configRepository
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        PaymentMethodRepositoryContract $paymentMethodRepository
    ) {
        $this->config = $configRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * Create the payment method ID if it doesn't exist yet
     *
     * @param PaymentMethodContract $paymentMethod
     */
    public function createMopIfNotExists(PaymentMethodContract $paymentMethod)
    {
        if ($this->getPaymentMethodIdByInstance($paymentMethod) === self::NO_PAYMENTMETHOD_FOUND) {
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
     * @param PaymentMethodContract $paymentMethod
     *
     * @return int
     */
    public function getPaymentMethodIdByInstance(PaymentMethodContract $paymentMethod): int
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
     * @param string $paymentMethod
     *
     * @return int
     */
    public function getPaymentMethodIdByClass(string $paymentMethod): int
    {
        /** @var PaymentMethodContract $methodInstance */
        $methodInstance = pluginApp($paymentMethod);

        return $this->getPaymentMethodIdByInstance($methodInstance);
    }

    /**
     * Returns the payment method key ('plugin_name::payment_key')
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getPluginPaymentMethodKey(PaymentMethodContract $paymentMethod): string
    {
        return Plugin::KEY . '::' . $this->getPaymentMethodKey($paymentMethod);
    }

    /**
     * Returns the available payment methods and their helper strings (config-key, payment-key, default name).
     *
     * @return string[]
     */
    public static function getPaymentMethods(): array
    {
        return static::$paymentMethods;
    }

    /**
     * Returns a list of events that should be observed.
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
     * @return string
     */
    public function getDomain(): string
    {
        /** @var WebstoreHelper $webstoreHelper */
        $webstoreHelper = pluginApp(WebstoreHelper::class);

        return $webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl;
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getFrontendEnabled(PaymentMethodContract $paymentMethod): string
    {
        return 'true';
    }

    /**
     * Returns the heidelpay authentication data (senderId, login, password, environment) as array.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return array
     */
    public function getHeidelpayAuthenticationConfig(PaymentMethodContract $paymentMethod): array
    {
        return [
            'SECURITY_SENDER' => $this->getSenderId(),
            'TRANSACTION_CHANNEL' => $this->getTransactionChannel($paymentMethod),
            'TRANSACTION_MODE' => $this->getEnvironment(),
            'USER_LOGIN' => $this->getUserLogin(),
            'USER_PWD' => $this->getUserPassword(),
        ];
    }

    /**
     * Returns the senderId for authentification.
     *
     * @return string
     */
    private function getSenderId(): string
    {
        return $this->config->get($this->getConfigKey(ConfigKeys::AUTH_SENDER_ID));
    }

    /**
     * Returns the user login for authentification.
     *
     * @return string
     */
    private function getUserLogin(): string
    {
        return $this->config->get($this->getConfigKey(ConfigKeys::AUTH_LOGIN));
    }

    /**
     * Returns the user password for authentification.
     *
     * @return string
     */
    private function getUserPassword(): string
    {
        return $this->config->get($this->getConfigKey(ConfigKeys::AUTH_PASSWORD));
    }

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return string
     */
    private function getEnvironment(): string
    {
        $transactionMode = (int) $this->config->get($this->getConfigKey(ConfigKeys::ENVIRONMENT));

        if ($transactionMode === 0) {
            return TransactionMode::CONNECTOR_TEST;
        }

        return TransactionMode::LIVE;
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    private function getTransactionChannel(PaymentMethodContract $paymentMethod): string
    {
        return $this->config->get($this->getChannelIdKey($paymentMethod));
    }

    /**
     * Returns if a payment method is enabled or disabled.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return bool
     */
    public function getIsActive(PaymentMethodContract $paymentMethod): bool
    {
        return $this->config->get($this->getIsActiveKey($paymentMethod)) === 'true';
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodName(PaymentMethodContract $paymentMethod): string
    {
        return $this->config->get($this->getDisplayNameKey($paymentMethod));
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
        return (float) str_replace(',', '.', $this->config->get($this->getMinAmountKey($paymentMethod)));
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
        return (float) str_replace(',', '.', $this->config->get($this->getMaxAmountKey($paymentMethod)));
    }

    /**
     * Returns the url to an icon for a payment method, if configured.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMethodIcon(PaymentMethodContract $paymentMethod): string
    {
        $useIcon = (bool) $this->config->get($this->getUseIconKey($paymentMethod));
        if ($useIcon === false) {
            return '';
        }

        return $this->config->get($this->getIconUrlKey($paymentMethod)) ?: '';
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMethodDescription(PaymentMethodContract $paymentMethod): string
    {
        $type = $this->getMethodDescriptionType($paymentMethod);

        if ($type === DescriptionTypes::INTERNAL) {
            return $this->config->get($this->getDescriptionKey($paymentMethod, true));
        }

        if ($type === DescriptionTypes::EXTERNAL) {
            return $this->config->get($this->getDescriptionKey($paymentMethod));
        }

        // in case of DescriptionTypes::NONE
        return '';
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    public function getMethodDescriptionType(PaymentMethodContract $paymentMethod): string
    {
        return $this->config->get($this->getDescriptionTypeKey($paymentMethod)) ?? DescriptionTypes::NONE;
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getPaymentMethodDefaultName(PaymentMethodContract $paymentMethod): string
    {
        $prefix = Plugin::NAME . ' - ';
        $name = $paymentMethod->getDefaultName() ?? self::NO_DEFAULT_NAME_FOUND;

        return $prefix . $name;
    }

    /**
     * Returns the complete config key (plugin name + config key) for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getConfigKey(string $key): string
    {
        return Plugin::NAME . '.' . $key;
    }

    /**
     * Returns the payment method config key for the 'Channel-ID' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getChannelIdKey(PaymentMethodContract $paymentMethod): string
    {
        $paymentMethodKey = $paymentMethod->getConfigKey();

        return $this->getConfigKey($paymentMethodKey . '.' . ConfigKeys::CHANNEL_ID);
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
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DISPLAY_NAME);
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
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_TYPE);
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
            return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_EXTERNAL);
        }

        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_INTERNAL);
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
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::IS_ACTIVE);
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
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MIN_AMOUNT);
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
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::MAX_AMOUNT);
    }

    /**
     * Returns the payment method config key for the 'use logo' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getUseIconKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_USE);
    }

    /**
     * Returns the payment method config key for the 'logo url' configuration.
     *
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getIconUrlKey(PaymentMethodContract $paymentMethod): string
    {
        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_URL);
    }

    /**
     * @param PaymentMethodContract $paymentMethod
     *
     * @return string
     */
    protected function getPaymentMethodKey(PaymentMethodContract $paymentMethod): string
    {
        return $paymentMethod->getMethodKey() ?? self::NO_KEY_FOUND;
    }
}
