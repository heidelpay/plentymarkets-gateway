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
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\OrderTxnIdRelation;
use Heidelpay\Models\Transaction;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentPropertyRepositoryContract;
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
 * @package heidelpay\plentymarkets-gateway\helpr
 */
class PaymentHelper
{
    // notification service won't work since the base service is not available on boot time
    use Loggable;

    const NO_PAYMENTMETHOD_FOUND = -1;

    /**
     * @var PaymentMethodRepositoryContract $paymentMethodRepo
     */
    protected $paymentMethodRepo;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepo;
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
     * @var OrderTxnIdRelationRepositoryContract
     */
    private $orderTxnIdRepo;
    /**
     * @var PaymentPropertyRepositoryContract
     */
    private $paymentPropertyRepo;

    /**
     * AbstractHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepo
     * @param OrderRepositoryContract $orderRepository
     * @param PaymentOrderRelationRepositoryContract $paymentOrderRepo
     * @param MainConfigContract $mainConfig
     * @param MethodConfigContract $methodConfig
     * @param OrderTxnIdRelationRepositoryContract $orderTxnIdRepo
     * @param PaymentPropertyRepositoryContract $propertyRepo
     */
    public function __construct(
        PaymentMethodRepositoryContract $paymentMethodRepo,
        OrderRepositoryContract $orderRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRepo,
        MainConfigContract $mainConfig,
        MethodConfigContract $methodConfig,
        OrderTxnIdRelationRepositoryContract $orderTxnIdRepo,
        PaymentPropertyRepositoryContract $propertyRepo
    ) {
        $this->paymentMethodRepo = $paymentMethodRepo;
        $this->orderRepo = $orderRepository;
        $this->paymentOrderRelationRepo = $paymentOrderRepo;
        $this->mainConfig = $mainConfig;
        $this->methodConfig = $methodConfig;
        $this->orderTxnIdRepo = $orderTxnIdRepo;
        $this->paymentPropertyRepo = $propertyRepo;
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
     * @param Transaction $paymentData
     *
     * @return int
     */
    public function mapToPlentyStatus(Transaction $paymentData): int
    {
        $paymentStatus = Payment::STATUS_CAPTURED;
        $processing = $paymentData->transactionProcessing;

        if (TransactionType::AUTHORIZE === $paymentData->transactionType ||
            (isset($processing['PROCESSING.STATUS_CODE']) && $processing['PROCESSING.STATUS_CODE'] === '80')) {
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
     * @return Order
     * @throws \RuntimeException
     */
    public function assignPlentyPaymentToPlentyOrder(Payment $payment, int $orderId): Order
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $additionalInfo = ['Order' => $order, 'Payment' => $payment];
        $this->getLogger(__METHOD__)->debug('payment.debugAssignPaymentToOrder', $additionalInfo);

        $this->paymentOrderRelationRepo->createOrderRelation($payment, $order);

        return $order;
    }

    /**
     * Returns a PaymentProperty with the given params
     *
     * @param int $typeId
     * @param string $value
     * @return PaymentProperty
     */
    public function getPaymentProperty(int $typeId, string $value): PaymentProperty
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
                $this->getLogger(__METHOD__)->critical('general.errorMethodNotFound', ['mopId' => $mop]);
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
                $this->getLogger(__METHOD__)->critical('general.errorMethodNotFound', ['Method' => $paymentMethod]);
                break;
        }
        return $instance;
    }

    /**
     * Adds the txnId to the order as external orderId.
     *
     * @param string $txnId
     * @param int $orderId
     */
    protected function assignTxnIdToOrder(string $txnId, int $orderId)
    {
        $order = $this->orderRepo->findOrderById($orderId);

        /** @var OrderProperty $orderProperty */
        $orderProperty = pluginApp(OrderProperty::class);
        $orderProperty->typeId = OrderPropertyType::EXTERNAL_ORDER_ID;
        $orderProperty->value = $txnId;
        $order->properties[] = $orderProperty;

        $this->orderRepo->updateOrder($order->toArray(), $order->id);
    }

    /**
     * @param string $txnId
     * @param int $mopId
     * @param int $orderId
     * @return OrderTxnIdRelation|null
     */
    public function createOrUpdateRelation(string $txnId, int $mopId, int $orderId = 0)
    {
        $relation =  $this->orderTxnIdRepo->createOrUpdateRelation($txnId, $mopId, $orderId);
        if ($orderId !== 0) {
            $this->assignTxnIdToOrder($txnId, $orderId);
        }
        return $relation;
    }

    /**
     * Returns the transaction part of the payment code.
     *
     * @param $txnObject
     * @return mixed
     * @throws \RuntimeException
     */
    public function getTransactionCode($txnObject)
    {
        $paymentCodeParts = explode('.', $txnObject['PAYMENT.CODE']);
        if (\count($paymentCodeParts) < 2) {
            throw new \RuntimeException('general.errorUnknownPaymentCode');
        }
        list(, $txnCode) = $paymentCodeParts;
        return $txnCode;
    }

    /**
     * Add a text before the current payment booking text.
     *
     * @param Payment $paymentObject
     * @param string $bookingText
     * @return $this
     */
    public function prependPaymentBookingText(Payment $paymentObject, string $bookingText): self
    {
        /** @var PaymentProperty $bookingTextProperty */
        $bookingTextProperty = $paymentObject->properties[PaymentProperty::TYPE_BOOKING_TEXT];
        $oldBookingText = $bookingTextProperty->value;
        $bookingText .= !empty($oldBookingText) ? ', ' . $oldBookingText : '';

        $bookingTextProperty->value = $bookingText;
        $this->paymentPropertyRepo->changeProperty((array) $bookingTextProperty);

        return $this;
    }

    /**
     * Fetches the Order object to the given orderId.
     *
     * @param int $orderId
     * @return Order
     * @throws \RuntimeException
     */
    private function getOrder(int $orderId): Order
    {
        $order = null;

        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);

        if (!empty($orderId)) { // Get the order by the given order ID
            $order = $authHelper->processUnguarded(
                function () use ($orderId) {
                    return $this->orderRepo->findOrderById($orderId);
                }
            );
        }

        // Check whether the order exists
        if (!$order instanceof Order) {
            throw new \RuntimeException('Order #' . $orderId . ' not found!');
        }
        return $order;
    }
}
