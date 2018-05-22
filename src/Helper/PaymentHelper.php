<?php

namespace Heidelpay\Helper;

use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
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
     * @var PaymentMethodRepositoryContract
     */
    protected $paymentMethodRepository;

    /**
     * @var array
     */
    public static $paymentMethods = [
        CreditCard::class => [
            self::ARRAY_KEY_CONFIG_KEY => CreditCard::CONFIG_KEY,
            self::ARRAY_KEY_KEY => CreditCard::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => CreditCard::DEFAULT_NAME,
        ],
        Prepayment::class => [
            self::ARRAY_KEY_CONFIG_KEY => Prepayment::CONFIG_KEY,
            self::ARRAY_KEY_KEY => Prepayment::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => Prepayment::DEFAULT_NAME,
        ],
        Sofort::class => [
            self::ARRAY_KEY_CONFIG_KEY => Sofort::CONFIG_KEY,
            self::ARRAY_KEY_KEY => Sofort::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => Sofort::DEFAULT_NAME,
        ],
        PayPal::class => [
            self::ARRAY_KEY_CONFIG_KEY => PayPal::CONFIG_KEY,
            self::ARRAY_KEY_KEY => PayPal::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => PayPal::DEFAULT_NAME,
        ],
    ];
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;
    /**
     * @var PaymentOrderRelationRepositoryContract
     */
    private $paymentOrderRelationRepo;
    /**
     * @var MainConfigContract
     */
    private $mainConfig;
    /**
     * @var MethodConfigContract
     */
    private $methodConfig;

    /**
     * AbstractHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     * @param OrderRepositoryContract $orderRepository
     * @param PaymentOrderRelationRepositoryContract $paymentOrderRelationRepo
     * @param MainConfigContract $mainConfig
     * @param MethodConfigContract $methodConfig
     */
    public function __construct(
        PaymentMethodRepositoryContract $paymentMethodRepository,
        OrderRepositoryContract $orderRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepo,
        MainConfigContract $mainConfig,
        MethodConfigContract $methodConfig
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentOrderRelationRepo = $paymentOrderRelationRepo;
        $this->mainConfig = $mainConfig;
        $this->methodConfig = $methodConfig;
    }

    /**
     * Create the payment method ID if it doesn't exist yet
     *
     * @param string $paymentMethodClass
     */
    public function createMopIfNotExists(string $paymentMethodClass)
    {
        if ($this->getPaymentMethodId($paymentMethodClass) === self::NO_PAYMENTMETHOD_FOUND) {
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
     * @return int
     */
    public function getPaymentMethodId(string $paymentMethodClass): int
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
     * Returns the available payment methods and their helper strings (config-key, payment-key, default name).
     *
     * @return string[]
     */
    public static function getPaymentMethods(): array
    {
        return array_keys(static::$paymentMethods);
    }

    // todo: remove?
//    /**
//     * Gets a certain key from a given payment method in the helper string array.
//     *
//     * @param string $paymentMethodClass
//     * @param string $key
//     *
//     * @return string
//     */
//    public function getPaymentMethodString(string $paymentMethodClass, string $key): string
//    {
//        return static::$paymentMethods[$paymentMethodClass][$key] ?? null;
//    }

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
            FrontendShippingCountryChanged::class
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
     * @return int
     */
    public function getWebstoreId(): int
    {
        /** @var WebstoreHelper $webstoreHelper */
        $webstoreHelper = pluginApp(WebstoreHelper::class);

        return $webstoreHelper->getCurrentWebstoreConfiguration()->webstoreId;
    }

    /**
     * Returns the heidelpay authentication data (senderId, login, password, environment) as array.
     *
     * @param string $paymentMethod
     *
     * @return array
     */
    public function getHeidelpayAuthenticationConfig(string $paymentMethod): array
    {
        return [
            'SECURITY_SENDER' => $this->mainConfig->getSenderId(),
            'TRANSACTION_CHANNEL' => $this->methodConfig->getTransactionChannel($paymentMethod),
            'TRANSACTION_MODE' => $this->mainConfig->getEnvironment(),
            'USER_LOGIN' => $this->mainConfig->getUserLogin(),
            'USER_PWD' => $this->mainConfig->getUserPassword(),
        ];
    }



    // todo: remove?
//    /**
//     * Returns the url to an icon for a payment method, if configured.
//     *
//     * @param PaymentMethodContract $paymentMethod
//     *
//     * @return string
//     */
//    public function getMethodIcon(PaymentMethodContract $paymentMethod): string
//    {
//        $useIcon = (bool) $this->config->get($this->getUseIconKey($paymentMethod));
//        if ($useIcon === false) {
//            return '';
//        }
//
//        return $this->config->get($this->getIconUrlKey($paymentMethod)) ?: '';
//    }

//    /**
//     * @param PaymentMethodContract $paymentMethod
//     *
//     * @return string
//     */
//    public function getMethodDescription(PaymentMethodContract $paymentMethod): string
//    {
//        $type = $this->getMethodDescriptionType($paymentMethod);
//
//        if ($type === DescriptionTypes::INTERNAL) {
//            return $this->config->get($this->getDescriptionKey($paymentMethod, true));
//        }
//
//        if ($type === DescriptionTypes::EXTERNAL) {
//            return $this->config->get($this->getDescriptionKey($paymentMethod));
//        }
//
//        // in case of DescriptionTypes::NONE
//        return '';
//    }

//    /**
//     * @param PaymentMethodContract $paymentMethod
//     *
//     * @return string
//     */
//    public function getMethodDescriptionType(PaymentMethodContract $paymentMethod): string
//    {
//        return $this->config->get($this->getDescriptionTypeKey($paymentMethod)) ?? DescriptionTypes::NONE;
//    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    protected function getPaymentMethodDefaultName(string $paymentMethod): string
    {
        $prefix = Plugin::NAME . ' - ';
        $name = static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_DEFAULT_NAME]
            ?? self::NO_DEFAULT_NAME_FOUND;

        return $prefix . $name;
    }

//    /**
//     * Returns the config key for the 'description/info page' configuration.
//     *
//     * @param PaymentMethodContract $paymentMethod
//     * @param bool           $isInternal
//     *
//     * @return string
//     */
//    protected function getDescriptionKey(PaymentMethodContract $paymentMethod, bool $isInternal = false): string
//    {
//        if (!$isInternal) {
//            return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_EXTERNAL);
//        }
//
//        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::DESCRIPTION_INTERNAL);
//    }

//    /**
//     * Returns the payment method config key for the 'use logo' configuration.
//     *
//     * @param PaymentMethodContract $paymentMethod
//     *
//     * @return string
//     */
//    protected function getUseIconKey(PaymentMethodContract $paymentMethod): string
//    {
//        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_USE);
//    }
//
//    /**
//     * Returns the payment method config key for the 'logo url' configuration.
//     *
//     * @param PaymentMethodContract $paymentMethod
//     *
//     * @return string
//     */
//    protected function getIconUrlKey(PaymentMethodContract $paymentMethod): string
//    {
//        return $this->getConfigKey($paymentMethod->getConfigKey() . '.' . ConfigKeys::LOGO_URL);
//    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    protected function getPaymentMethodKey(string $paymentMethod): string
    {
        return static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_KEY] ?? self::NO_KEY_FOUND;
    }

    /**
     * Maps the transaction result into a plentymarkets Payment status ID.
     *
     * @param array $paymentData
     *
     * @return int
     */
    public function mapToPlentyStatus(array $paymentData): int
    {
        $paymentStatus = Payment::STATUS_CAPTURED;

        if (isset($paymentData['PROCESSING.STATUS_CODE']) && $paymentData['PROCESSING.STATUS_CODE'] === '80') {
            $paymentStatus = Payment::STATUS_AWAITING_APPROVAL;
        }

        return $paymentStatus;
    }

    /**
     * Maps a heidelpay transaction response to a custom status code for this plugin.
     *
     * @param array $paymentData
     *
     * @return int
     */
    public function mapHeidelpayTransactionStatus(array $paymentData): int
    {
        if ($paymentData['isSuccess'] === true) {
            return TransactionStatus::ACK;
        }

        if ($paymentData['isPending'] === true) {
            return TransactionStatus::PENDING;
        }

        return TransactionStatus::NOK;
    }

    /**
     * @param string $paymentCode
     *
     * @return string
     */
    public function mapHeidelpayTransactionType(string $paymentCode): string
    {
        if (strpos($paymentCode, '.')) {
            list(, $transactionType) = explode('.', $paymentCode);
        } else {
            list(, $transactionType) = explode('_', $paymentCode);
        }

        switch ($transactionType) {
            case TransactionType::HP_AUTHORIZE:
                $result = TransactionType::AUTHORIZE;
                break;
            case TransactionType::HP_CREDIT:
                $result = TransactionType::CREDIT;
                break;
            case TransactionType::HP_DEBIT:
                $result = TransactionType::DEBIT;
                break;
            case TransactionType::HP_CAPTURE:
                $result = TransactionType::CAPTURE;
                break;
            case TransactionType::HP_RECEIPT:
                $result = TransactionType::RECEIPT;
                break;
            case TransactionType::HP_FINALIZE:
                $result = TransactionType::FINALIZE;
                break;
            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     * Assign the payment to an order in plentymarkets
     *
     * @param Payment $payment
     * @param int $orderId
     */
    public function assignPlentyPaymentToPlentyOrder(Payment $payment, int $orderId)
    {
        // Get the order by the given order ID
        $order = $this->orderRepository->findOrderById($orderId);

        // Check whether the order truly exists in plentymarkets
        if ($order instanceof Order) {
            // Assign the given payment to the given order
            $this->paymentOrderRelationRepo->createOrderRelation($payment, $order);
        }
    }

    /**
     * Returns a PaymentProperty with the given params
     *
     * @param $typeId
     * @param $value
     * @return PaymentProperty
     */
    public function getPaymentProperty($typeId, $value): PaymentProperty
    {
        /** @var PaymentProperty $paymentProperty */
        $paymentProperty = pluginApp(PaymentProperty::class);

        $paymentProperty->typeId = $typeId;
        $paymentProperty->value = $value;

        return $paymentProperty;
    }
}
