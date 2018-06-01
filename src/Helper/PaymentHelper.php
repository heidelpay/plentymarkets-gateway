<?php

namespace Heidelpay\Helper;

use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\DirectDebit;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Heidelpay\Services\NotificationService;
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
    const NO_PAYMENTMETHOD_FOUND = -1;

    /**
     * @var PaymentMethodRepositoryContract $paymentMethodRepo
     */
    protected $paymentMethodRepo;
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
     * @var NotificationService
     */
    private $notification;

    /**
     * AbstractHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepo
     * @param OrderRepositoryContract $orderRepository
     * @param PaymentOrderRelationRepositoryContract $paymentOrderRepo
     * @param MainConfigContract $mainConfig
     * @param MethodConfigContract $methodConfig
     * @param NotificationService $notification
     */
    public function __construct(
        PaymentMethodRepositoryContract $paymentMethodRepo,
        OrderRepositoryContract $orderRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRepo,
        MainConfigContract $mainConfig,
        MethodConfigContract $methodConfig,
        NotificationService $notification
    ) {
        $this->paymentMethodRepo = $paymentMethodRepo;
        $this->orderRepository = $orderRepository;
        $this->paymentOrderRelationRepo = $paymentOrderRepo;
        $this->mainConfig = $mainConfig;
        $this->methodConfig = $methodConfig;
        $this->notification = $notification;
    }

    /**
     * Create the payment method IDs that don't exist yet.
     */
    public function createMopsIfNotExists()
    {
        foreach ($this->methodConfig::getPaymentMethods() as $paymentMethod) {
            $this->createMopIfNotExists($paymentMethod);
        }
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
                'paymentKey' => $this->methodConfig->getPaymentMethodKey($paymentMethodClass),
                'name' => $this->methodConfig->getPaymentMethodDefaultName($paymentMethodClass)
            ];

            $this->paymentMethodRepo->createPaymentMethod($paymentMethodData);
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
        $paymentMethods = $this->paymentMethodRepo->allForPlugin(Plugin::KEY);

        if (!empty($paymentMethods)) {
            /** @var PaymentMethod $payMethod */
            foreach ($paymentMethods as $payMethod) {
                if ($payMethod->paymentKey === $this->methodConfig->getPaymentMethodKey($paymentMethodClass)) {
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
        return Plugin::KEY . '::' . $this->methodConfig->getPaymentMethodKey($paymentMethodClass);
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

    /**
     * @param $mop
     * @return string
     */
    public function mapMopToPaymentMethod($mop): string
    {
        $paymentMethod = '';

        switch ($mop) {
            case $this->getPaymentMethodId(CreditCard::class):
                $paymentMethod = CreditCard::class;
                break;
            case $this->getPaymentMethodId(DebitCard::class):
                $paymentMethod = DebitCard::class;
                break;
            case $this->getPaymentMethodId(Sofort::class):
                $paymentMethod = Sofort::class;
                break;
            case $this->getPaymentMethodId(DirectDebit::class):
                $paymentMethod = DirectDebit::class;
                break;
            default:
                // do nothing
                $this->notification->critical('general.errorMethodNotFound', __METHOD__, ['mopId' => $mop]);
                break;
        }

        return $paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentMethodContract|null
     */
    public function getPaymentMethodInstance(string $paymentMethod)
    {
        /** @var PaymentMethodContract $instance */
        $instance = null;

        switch ($paymentMethod) {
            case CreditCard::class:
                $instance = pluginApp(CreditCard::class);
                break;

            case DebitCard::class:
                $instance = pluginApp(DebitCard::class);
                break;

            case PayPal::class:
                $instance = pluginApp(PayPal::class);
                break;

            case Sofort::class:
                $instance = pluginApp(Sofort::class);
                break;

            case Prepayment::class:
                $instance = pluginApp(Prepayment::class);
                break;

            case DirectDebit::class:
                $instance = pluginApp(DirectDebit::class);
                break;

            default:
                // do nothing
                $this->notification->critical('general.errorMethodNotFound', __METHOD__, ['Method' => $paymentMethod]);
                break;
        }
        return $instance;
    }
}
