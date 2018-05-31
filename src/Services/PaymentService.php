<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\Routes;
use Heidelpay\Constants\Salutation;
use Heidelpay\Constants\SessionKeys;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\DirectDebit;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentOrderRelation;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;

/**
 * heidelpay Payment Service class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */
class PaymentService
{
    use Loggable;

    const CARD_METHODS = [CreditCard::class, DebitCard::class];

    /**
     * @var array
     */
    private $heidelpayRequest = [];

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;

    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var PaymentOrderRelationRepositoryContract
     */
    private $paymentOrderRelationRepository;

    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var LibService
     */
    private $libService;

    /**
     * @var Twig
     */
    private $twig;
    /**
     * @var TransactionRepositoryContract
     */
    private $transactionRepository;
    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $sessionStorageFactory;

    /**
     * PaymentService constructor.
     *
     * @param AddressRepositoryContract $addressRepository
     * @param CountryRepositoryContract $countryRepository
     * @param LibService $libraryService
     * @param OrderRepositoryContract $orderRepository
     * @param PaymentOrderRelationRepositoryContract $paymentOrderRelationRepository
     * @param PaymentRepositoryContract $paymentRepository
     * @param TransactionRepositoryContract $transactionRepo
     * @param PaymentHelper $paymentHelper
     * @param Twig $twig
     * @param FrontendSessionStorageFactoryContract $sessionStorageFactory
     */
    public function __construct(
        AddressRepositoryContract $addressRepository,
        CountryRepositoryContract $countryRepository,
        LibService $libraryService,
        OrderRepositoryContract $orderRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepository,
        PaymentRepositoryContract $paymentRepository,
        TransactionRepositoryContract $transactionRepo,
        PaymentHelper $paymentHelper,
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorageFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->countryRepository = $countryRepository;
        $this->libService = $libraryService;
        $this->orderRepository = $orderRepository;
        $this->paymentOrderRelationRepository = $paymentOrderRelationRepository;
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepo;
        $this->paymentHelper = $paymentHelper;
        $this->twig = $twig;
        $this->sessionStorageFactory = $sessionStorageFactory;
    }

    /**
     * Executes payment tasks after an order has been created.
     *
     * @param string         $paymentMethod
     * @param ExecutePayment $event
     *
     * @return array
     */
    public function executePayment(string $paymentMethod, ExecutePayment $event): array
    {
        $this->getLogger(__METHOD__)->debug('heidelpay::payment.debugExecutePayment', [
            'paymentMethod' => $paymentMethod,
            'mopId' => $event->getMop(),
            'orderId' => $event->getOrderId()
        ]);

        $transactionDetails = [];
        $transaction = null;

        // Retrieve heidelpay Transaction by txnId to get values needed for plenty payment (e.g. amount etc).
        $transactionId = $this->sessionStorageFactory->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $transactions = $this->transactionRepository->getTransactionsByTxnId($transactionId);
        $this->getLogger(__METHOD__)->critical('Transactions', $transactions);
        foreach ($transactions as $transaction) {
            $this->getLogger(__METHOD__)->critical('Transaction', $transaction);
            $allowedStatus = [TransactionStatus::ACK, TransactionStatus::PENDING];
            if (\in_array($transaction->status, $allowedStatus, false)) {
                $transactionDetails = $transaction->transactionDetails;
                break;
            }
        }

        if (!($transaction instanceof Transaction) ||
            !isset($transactionDetails['PRESENTATION.AMOUNT'], $transactionDetails['PRESENTATION.CURRENCY'])) {
            return ['error', 'heidelpay::error.errorDuringPaymentExecution'];
        }

        $plentyPayment = $this->createPlentyPayment($transaction, $transaction->paymentMethodId);
        if (!($plentyPayment instanceof Payment)) {
            return ['error', 'heidelpay::error.errorDuringPaymentExecution'];
        }

        $this->paymentHelper->assignPlentyPaymentToPlentyOrder($plentyPayment, $event->getOrderId());

        return ['success', 'heidelpay::info.infoPaymentSuccessful'];
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     * @param string $transactionType
     * @param int $mopId
     * @param array $parameters
     *
     * @return array
     */
    public function sendPaymentRequest(
        Basket $basket,
        string $paymentMethod,
        string $transactionType,
        int $mopId,
        array $parameters = []
    ): array {
        $this->prepareRequest($basket, $paymentMethod, $mopId);

        $result = $this->libService->sendTransactionRequest($paymentMethod, [
            'request' => $this->heidelpayRequest,
            'transactionType' => $transactionType,
            'parameters' => $parameters
        ]);

        return $result;
    }

    /**
     * Depending on the given payment method, return some information
     * regarding the payment method, or just continue the process.
     *
     * @param string $paymentMethod
     * @param Basket $basket
     * @param int    $mopId
     *
     * @return array
     */
    public function getPaymentMethodContent(
        string $paymentMethod,
        Basket $basket,
        int $mopId
    ): array {
        $value = '';

        /** @var AbstractMethod $methodInstance */
        $methodInstance = $this->getPaymentMethodInstance($paymentMethod);

        if (!$methodInstance instanceof PaymentMethodContract) {
            $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
            $value = 'heidelpay::payment.errorInternalErrorTryAgainLater';
            return ['type' => $type, 'value' => $value];
        }

        $type = $methodInstance->getReturnType();

        if ($type === GetPaymentMethodContent::RETURN_TYPE_CONTINUE) {
            return ['type' => $type, 'value' => $value];
        }

        if ($methodInstance->hasToBeInitialized()) {
            $result = $this->sendPaymentRequest($basket, $paymentMethod, $methodInstance->getTransactionType(), $mopId);
            try {
                $value = $this->handleSyncResponse($type, $result);
            } catch (\RuntimeException $e) {
                $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
                $value = $e->getMessage();
            }
        }

        switch ($type) {
            case GetPaymentMethodContent::RETURN_TYPE_EXTERNAL_CONTENT_URL:
                $value = $this->renderPaymentForm(
                    $methodInstance->getFormTemplate(),
                    ['paymentFrameUrl' => $value, 'paymentMethod' => $paymentMethod]
                );
                break;
            case GetPaymentMethodContent::RETURN_TYPE_HTML:
                $value = $this->renderPaymentForm(
                    $methodInstance->getFormTemplate(),
                    ['formSubmitUrl' => Routes::SEND_PAYMENT_REQUEST, 'mopId' => $mopId, 'paymentMethod' => $paymentMethod]
                );
                break;
            default:
                // do nothing in any other case
                break;
        }

        return ['type' => $type, 'value' => $value];
    }

    /**
     * @param string $type
     * @param $response
     * @return mixed
     * @throws \RuntimeException
     */
    private function handleSyncResponse(string $type, $response)
    {
        if (!\is_array($response)) {
            return $response;
        }

        // return the exception message, if present.
        if (isset($response['exceptionCode'])) {
            throw new \RuntimeException($response['exceptionCode']);
        }

        // return rendered html content
        $haystack = [GetPaymentMethodContent::RETURN_TYPE_HTML, GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL];
        if (!$response['isSuccess'] && \in_array($type, $haystack, true)) {
            throw new \RuntimeException($response['response']['PROCESSING.RETURN']);
        }

        // return the payment frame url, if it is needed
        if ($type === GetPaymentMethodContent::RETURN_TYPE_HTML) {
            return $response['response']['FRONTEND.PAYMENT_FRAME_URL'];
        }

        // return the redirect url, if present.
        if ($type === GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL) {
            return $response['response']['FRONTEND.REDIRECT_URL'];
        }

        return $response;
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     * @param int    $mopId
     */
    private function prepareRequest(Basket $basket, string $paymentMethod, int $mopId)
    {
        // set authentification data
        $heidelpayAuth = $this->paymentHelper->getHeidelpayAuthenticationConfig($paymentMethod);
        $this->heidelpayRequest = array_merge($this->heidelpayRequest, $heidelpayAuth);

        // set customer personal information & address data
        $addresses = $this->getCustomerAddressData($basket);
        $this->heidelpayRequest['IDENTIFICATION_SHOPPERID'] = $basket->customerId;
        $this->heidelpayRequest['NAME_GIVEN'] = $addresses['billing']->firstName;
        $this->heidelpayRequest['NAME_FAMILY'] = $addresses['billing']->lastName;
        $this->heidelpayRequest['CONTACT_EMAIL'] = $addresses['billing']->email;
        $this->heidelpayRequest['ADDRESS_STREET'] = $this->getFullStreetAndHouseNumber($addresses['billing']);
        $this->heidelpayRequest['ADDRESS_ZIP'] = $addresses['billing']->postalCode;
        $this->heidelpayRequest['ADDRESS_CITY'] = $addresses['billing']->town;
        $this->heidelpayRequest['ADDRESS_COUNTRY'] = $this->countryRepository->findIsoCode(
            $addresses['billing']->countryId,
            'isoCode2'
        );

        if ($addresses['billing']->companyName !== null) {
            $this->heidelpayRequest['NAME_COMPANY'] = $addresses['billing']->companyName;
        }

        // create transactionId and store it in the customer session to fetch the correct transaction later.
//        $transactionId = uniqid('', false);
        // todo: ersetzen durch noch eindeutigere id
        $transactionId = time();
        $this->sessionStorageFactory->getPlugin()->setValue(SessionKeys::SESSION_KEY_TXN_ID, $transactionId);
        $this->heidelpayRequest['IDENTIFICATION_TRANSACTIONID'] = $transactionId;

        // set basket information (amount, currency, orderId, ...)
        $this->heidelpayRequest['PRESENTATION_AMOUNT'] = $basket->basketAmount;
        $this->heidelpayRequest['PRESENTATION_CURRENCY'] = $basket->currency;

        // TODO: receive frontend language somehow.
        $this->heidelpayRequest['FRONTEND_ENABLED'] = 'TRUE';
        $this->heidelpayRequest['FRONTEND_LANGUAGE'] = 'DE';
        $this->heidelpayRequest['FRONTEND_RESPONSE_URL'] =
            $this->paymentHelper->getDomain() . '/' . Routes::RESPONSE_URL;

        // add the origin domain, which is important for the CSP
        // set 'PREVENT_ASYNC_REDIRECT' to false, to ensure the customer is being redirected after submitting the form.
        if (\in_array($paymentMethod, self::CARD_METHODS, true)) {
            $this->heidelpayRequest['FRONTEND_PAYMENT_FRAME_ORIGIN'] = $this->paymentHelper->getDomain();
            $this->heidelpayRequest['FRONTEND_PREVENT_ASYNC_REDIRECT'] = 'false';
        }

        // TODO: Secure information for B2C payment methods
        if (false) {
            $this->heidelpayRequest['NAME_SALUTATION'] = $addresses['billing']->gender === 'male'
                ? Salutation::MR
                : Salutation::MRS;

            $this->heidelpayRequest['NAME_BIRTHDATE'] = $addresses['billing']->birthday;
            $this->heidelpayRequest['BASKET_ID'] = $this->getBasketId($basket, $heidelpayAuth);
        }

        // shop + module information
        $this->heidelpayRequest['CRITERION_STORE_ID'] = $this->paymentHelper->getWebstoreId();
        $this->heidelpayRequest['CRITERION_MOP'] = $mopId;
        $this->heidelpayRequest['CRITERION_SHOP_TYPE'] = 'plentymarkets 7';
        $this->heidelpayRequest['CRITERION_SHOPMODULE_VERSION'] = Plugin::VERSION;
        $this->heidelpayRequest['CRITERION_BASKET_ID'] = $basket->id;
        $this->heidelpayRequest['CRITERION_ORDER_ID'] = $basket->orderId;
        $this->heidelpayRequest['CRITERION_ORDER_TIMESTAMP'] = $basket->orderTimestamp;
        $this->heidelpayRequest['CRITERION_PUSH_URL'] =
            $this->paymentHelper->getDomain() . '/' . Routes::PUSH_NOTIFICATION_URL;

        // TODO: Riskinformation for future payment methods

        $this->getLogger(__METHOD__)->debug('heidelpay::request.debugPreparingRequest', $this->heidelpayRequest);
    }

    //<editor-fold desc="Handlers">
    /**
     * Handles the asynchronous response coming from the heidelpay API.
     *
     * @param array $post
     *
     * @return array
     */
    public function handleAsyncPaymentResponse(array $post): array
    {
        return $this->libService->handleResponse($post);
    }

    /**
     * Calls the handler for the push notification processing.
     *
     * @param array $post
     *
     * @return array
     */
    public function handlePushNotification(array $post): array
    {
        return $this->libService->handlePushNotification($post);
    }
    //</editor-fold>

    /**
     * Submits the Basket to the Basket-API and returns its ID.
     *
     * @param Basket $basket
     * @param array  $authData
     *
     * @return string
     */
    private function getBasketId(Basket $basket, array $authData): string
    {
        $params = [];
        $params['auth'] = [
            'login' => $authData['USER_LOGIN'],
            'password' => $authData['USER_PWD'],
            'senderId' => $authData['SECURITY_SENDER'],
        ];
        $params['basket'] = $basket->toArray();

        $response = $this->libService->submitBasket($params);
        return $response['basketId'];
    }

    /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array.
     *
     * @param Basket $basket
     *
     * @return Address[]
     */
    private function getCustomerAddressData(Basket $basket): array
    {
        $addresses = [];
        $addresses['billing'] = $this->addressRepository->findAddressById($basket->customerInvoiceAddressId);

        // if the shipping address is -99 or null, it is matching the billing address.
        if ($basket->customerShippingAddressId === null || $basket->customerShippingAddressId === -99) {
            $addresses['shipping'] = $addresses['billing'];
            return $addresses;
        }

        $addresses['shipping'] = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
        return $addresses;
    }

    /**
     * Returns street and house number as a single string.
     *
     * @param Address $address
     *
     * @return string
     */
    private function getFullStreetAndHouseNumber(Address $address): string
    {
        return $address->street . ' ' . $address->houseNumber;
    }

    /**
     * Creates a plentymarkets payment entity.
     *
     * @param Transaction $paymentData
     * @param int $paymentMethodId
     *
     * @return Payment
     */
    public function createPlentyPayment(Transaction $paymentData, int $paymentMethodId): Payment
    {
        $paymentDetails = $paymentData->transactionDetails;

        /** @var Payment $payment */
        $payment = pluginApp(Payment::class);
        $payment->mopId = $paymentMethodId;
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $payment->status = $this->paymentHelper->mapToPlentyStatus($paymentDetails);
        $payment->amount = $paymentDetails['PRESENTATION.AMOUNT'];
        $payment->currency = $paymentDetails['PRESENTATION.CURRENCY'];
        $payment->receivedAt = date('Y-m-d H:i:s');
        $payment->status = $this->paymentHelper->mapToPlentyStatus($paymentData->transactionProcessing);
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $payment->type = Payment::PAYMENT_TYPE_CREDIT; // From Merchant point of view

        // todo: Keine Zuordnung möglich: unaccountable (kann das passieren?)
////        if(!empty($paymentData['unaccountable']))
////        {
////            $payment->unaccountable = $paymentData['unaccountable'];
////        }

        $paymentProperty = [];
        $paymentProperty[] = $this->paymentHelper->getPaymentProperty(
            PaymentProperty::TYPE_ORIGIN,
            Payment::ORIGIN_PLUGIN
        );

        $paymentProperty[] = $this->paymentHelper->getPaymentProperty(
            PaymentProperty::TYPE_TRANSACTION_ID,
            (int)$paymentData->txnId
        );

//        $paymentProperty[] = $this->paymentHelper->getPaymentProperty(
//            PaymentProperty::TYPE_TRANSACTION_ID,
//            $paymentData->txnId
//        );
//        $paymentProperty[] = $this->paymentHelper->getPaymentProperty(
//            PaymentProperty::TYPE_BOOKING_TEXT,
//            'TransactionId: ' . $paymentData->txnId
//        );

        // create the payment
        $payment->properties = $paymentProperty;
        $payment->regenerateHash = true;
        $this->getLogger(__METHOD__)->debug('heidelpay::payment.debugCreatePlentyPayment', [$payment]);

        return $this->paymentRepository->createPayment($payment);
    }

    /**
     * Assigns a payment to an order.
     *
     * @param Payment $payment
     * @param int     $orderId
     *
     * @return bool
     */
    public function assignPaymentToOrder(Payment $payment, int $orderId): bool
    {
        $order = $this->orderRepository->findOrderById($orderId);

        if ($order instanceof Order) {
            $paymentOrderRelation = $this->paymentOrderRelationRepository->createOrderRelation($payment, $order);

            return $paymentOrderRelation instanceof PaymentOrderRelation;
        }

        return false;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentMethodContract|null
     */
    protected function getPaymentMethodInstance(string $paymentMethod)
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
                $instance = pluginApp(Sofort::class);
                break;

            case DirectDebit::class:
                $instance = pluginApp(Sofort::class);
                break;

            default:
                break;
        }
        return $instance;
    }

    /**
     * Returns the transaction type returned by the payment method.
     *
     * @param $paymentMethod
     * @return string
     * @throws \RuntimeException
     */
    public function getTransactionType($paymentMethod): string
    {
        $methodInstance = $this->getPaymentMethodInstance($paymentMethod);
        if (!$methodInstance instanceof PaymentMethodContract) {
            throw new \RuntimeException('Error creating payment instance.');
        }
        return $methodInstance->getTransactionType();
    }

    /**
     * Renders the given template injecting the parameters
     *
     * @param string $template
     * @param array $parameters
     * @return string
     */
    protected function renderPaymentForm(string $template, array $parameters = []): string
    {
        return $this->twig->render($template, $parameters);
    }
}
