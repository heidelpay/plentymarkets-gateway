<?php

namespace Heidelpay\Services;

use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\Routes;
use Heidelpay\Constants\SessionKeys;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\OrderTxnIdRelation;
use Heidelpay\Models\Transaction;
use Heidelpay\Traits\Translator;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Templates\Twig;

/**
 * Provides service methods to handle payments.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */
class PaymentService
{
    use Translator;

    const CARD_METHODS = [CreditCard::class, DebitCard::class];

    /**
     * @var array
     */
    private $heidelpayRequest = [];
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
     * @var NotificationServiceContract
     */
    private $notification;
    /**
     * @var MethodConfigContract
     */
    private $methodConfig;
    /**
     * @var OrderTxnIdRelationRepositoryContract
     */
    private $orderTxnIdRepo;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepo;
    /**
     * @var UrlServiceContract
     */
    private $urlService;
    /**
     * @var ContactRepositoryContract
     */
    private $contactRepo;
    /**
     * @var BasketServiceContract
     */
    private $basketService;
    /**
     * @var CommentRepositoryContract
     */
    private $commentRepo;

    /**
     * PaymentService constructor.
     *
     * @param LibService $libraryService
     * @param PaymentRepositoryContract $paymentRepository
     * @param TransactionRepositoryContract $transactionRepo
     * @param PaymentHelper $paymentHelper
     * @param Twig $twig
     * @param FrontendSessionStorageFactoryContract $sessionStorageFac
     * @param NotificationServiceContract $notification
     * @param MethodConfigContract $methodConfig
     * @param OrderTxnIdRelationRepositoryContract $orderTxnIdRepo
     * @param OrderRepositoryContract $orderRepo
     * @param UrlServiceContract $urlService
     * @param BasketServiceContract $basketService
     * @param ContactRepositoryContract $contactRepo
     */
    public function __construct(
        LibService $libraryService,
        PaymentRepositoryContract $paymentRepository,
        TransactionRepositoryContract $transactionRepo,
        PaymentHelper $paymentHelper,
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorageFac,
        NotificationServiceContract $notification,
        MethodConfigContract $methodConfig,
        OrderTxnIdRelationRepositoryContract $orderTxnIdRepo,
        OrderRepositoryContract $orderRepo,
        UrlServiceContract $urlService,
        BasketServiceContract $basketService,
        ContactRepositoryContract $contactRepo
    ) {
        $this->libService = $libraryService;
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepo;
        $this->paymentHelper = $paymentHelper;
        $this->twig = $twig;
        $this->sessionStorageFactory = $sessionStorageFac;
        $this->notification = $notification;
        $this->methodConfig = $methodConfig;
        $this->orderTxnIdRepo = $orderTxnIdRepo;
        $this->orderRepo = $orderRepo;
        $this->urlService = $urlService;
        $this->contactRepo = $contactRepo;
        $this->basketService = $basketService;
    }

    /**
     * Executes payment tasks after an order has been created.
     *
     * @param string $paymentMethod
     * @param ExecutePayment $event
     *
     * @return array
     */
    public function executePayment(string $paymentMethod, ExecutePayment $event): array
    {
        $orderId = $event->getOrderId();
        $mopId = $event->getMop();

        $logData = ['paymentMethod' => $paymentMethod, 'mopId' => $mopId, 'orderId' => $orderId];
        $this->notification->debug('payment.debugExecutePayment', __METHOD__, $logData);

        $transactionDetails = [];
        $transaction = null;

        // Retrieve heidelpay Transaction by txnId to get values needed for plenty payment (e.g. amount etc).
        $txnId = $this->sessionStorageFactory->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $this->createOrUpdateRelation($txnId, $mopId, $orderId);

        $transactions = $this->transactionRepository->getTransactionsByTxnId($txnId);
        foreach ($transactions as $transaction) {
            $allowedStatus = [TransactionStatus::ACK, TransactionStatus::PENDING];
            if (\in_array($transaction->status, $allowedStatus, false)) {
                $transactionDetails = $transaction->transactionDetails;
                break;
            }
        }

        try {
            if (empty($transactionDetails)) {
                throw new \RuntimeException('Could not find transaction!');
            }
            if (!isset($transactionDetails['PRESENTATION.AMOUNT'], $transactionDetails['PRESENTATION.CURRENCY'])) {
                throw new \RuntimeException('Amount or currency is empty');
            }
        } catch (\RuntimeException $exception) {
            $this->notification->error($exception->getMessage(), __METHOD__, ['Transaction' => $transaction], true);
            return ['error', 'Heidelpay::error.errorDuringPaymentExecution'];
        }

        try {
            $this->handleTransaction($transaction);
        } catch (\RuntimeException $e) {
            return ['error', $e->getMessage()];
        }

        return ['success', 'Heidelpay::info.infoPaymentSuccessful'];
    }

    /**
     * Send request to PaymentApi.
     *
     * @param Basket $basket
     * @param string $paymentMethod
     * @param string $transactionType
     * @param int $mopId
     * @param array $additionalParams
     *
     * @return array
     * @throws \RuntimeException
     */
    public function sendPaymentRequest(
        Basket $basket,
        string $paymentMethod,
        string $transactionType,
        int $mopId,
        array $additionalParams = []
    ): array {

        $txnId = $this->createNewTxnId($basket);
        $this->createOrUpdateRelation($txnId, $mopId);
        $this->prepareRequest($basket, $paymentMethod, $mopId, $txnId, $additionalParams);

        $result = $this->libService->sendTransactionRequest($paymentMethod, [
            'request' => $this->heidelpayRequest,
            'transactionType' => $transactionType
        ]);

        return $result;
    }

    /**
     * Return information on how plenty has to handle the payment method.
     * E.g. show html-content (e.g. iFrame) or redirect to external url (e.g. Sofort etc.).
     *
     * @param string $paymentMethod
     * @param int    $mopId
     *
     * @return array
     */
    public function getPaymentMethodContent(
        string $paymentMethod,
        int $mopId
    ): array {
        $value = '';

        $clientErrorMessage = 'Heidelpay::payment.errorInternalErrorTryAgainLater';

        /** @var AbstractMethod $methodInstance */
        $methodInstance = $this->paymentHelper->getPaymentMethodInstance($paymentMethod);
        if (!$methodInstance instanceof PaymentMethodContract) {
            $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
            $value = $clientErrorMessage;
            return [$type, $value];
        }

        if ($methodInstance->needsMatchingAddresses() && !$this->basketService->shippingMatchesBillingAddress()) {
            $this->notification->error(
                'payment.addressesShouldMatch',
                __METHOD__,
                [
                   'billing' => $this->basketService->getCustomerAddressData()['billing']->toArray(),
                   'shipping' => $this->basketService->getCustomerAddressData()['shipping']->toArray()
                ],
                true
            );
            return [GetPaymentMethodContent::RETURN_TYPE_ERROR, 'Heidelpay::payment.addressesShouldMatch'];
        }

        $type = $methodInstance->getReturnType();
        if ($type === GetPaymentMethodContent::RETURN_TYPE_CONTINUE) {
            return [$type, $value];
        }

        $value = $this->urlService->generateURL(Routes::HANDLE_FORM_URL);

        $basket     = $this->basketService->getBasket();
        if ($methodInstance->hasToBeInitialized()) {
            try {
                $transactionType = $methodInstance->getTransactionType();
                $result          = $this->sendPaymentRequest($basket, $paymentMethod, $transactionType, $mopId);
                $value           = $this->handleSyncResponse($type, $result);
            } catch (\RuntimeException $e) {
                $this->notification->error($clientErrorMessage, __METHOD__, [$type, $e->getMessage()], true);
                $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
                $value = $this->getTranslator()->trans($clientErrorMessage);
                return [$type, $value];
            }
        }

        $customerId = $basket->customerId;
        $contact    = $customerId ? $this->contactRepo->findContactById($customerId) : null;
        $birthday   = $contact ? explode('-', substr($contact->birthdayAt, 0, 10)): null;

        if ($type === GetPaymentMethodContent::RETURN_TYPE_HTML) {
            // $value should contain the payment frame url (also form url)
            $parameters = [
                'submit_action' => $value,
                'customer_dob_day' => $birthday[2] ?? '',
                'customer_dob_month' => $birthday[1] ?? '',
                'customer_dob_year' => $birthday[0] ?? '',
                'customer_gender' => $contact->gender
            ];
            $value      = $this->renderPaymentForm($methodInstance->getFormTemplate(), $parameters);
        }

        return [$type, $value];
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     * @param int $mopId
     * @param string $transactionId
     * @param array $additionalParams
     * @throws \RuntimeException
     */
    private function prepareRequest(
        Basket $basket,
        string $paymentMethod,
        int $mopId,
        string $transactionId,
        array $additionalParams = [])
    {
        $basketArray = $basket->toArray();

        /** @var SecretService $secretService */
        $secretService = pluginApp(SecretService::class);

        /** @var PaymentMethodContract $methodInstance */
        $methodInstance = $this->paymentHelper->getPaymentMethodInstance($paymentMethod);

        // set authentication data
        $heidelpayAuth = $this->paymentHelper->getHeidelpayAuthenticationConfig($paymentMethod);
        $this->heidelpayRequest = array_merge($this->heidelpayRequest, $heidelpayAuth);

        // set customer personal information & address data
        $addresses      = $this->basketService->getCustomerAddressData();
        $billingAddress = $addresses['billing'];
        $this->heidelpayRequest['IDENTIFICATION_SHOPPERID'] = $basketArray['customerId'];
        $this->heidelpayRequest['NAME_GIVEN']               = $billingAddress->firstName;
        $this->heidelpayRequest['NAME_FAMILY']              = $billingAddress->lastName;
        $this->heidelpayRequest['CONTACT_EMAIL']            = $billingAddress->email;
        $this->heidelpayRequest['ADDRESS_STREET']           = $this->getFullStreetAndHouseNumber($billingAddress);
        $this->heidelpayRequest['ADDRESS_ZIP']              = $billingAddress->postalCode;
        $this->heidelpayRequest['ADDRESS_CITY']             = $billingAddress->town;
        $this->heidelpayRequest['ADDRESS_COUNTRY']          = $this->basketService->getBillingCountryCode();

        if ($this->basketService->isBasketB2B()) {
            $this->heidelpayRequest['NAME_COMPANY'] = $billingAddress->companyName;
        }

        $this->heidelpayRequest['IDENTIFICATION_TRANSACTIONID'] = $transactionId;

        // set amount to net if showNetPrice === true
        if ($this->sessionStorageFactory->getCustomer()->showNetPrice) {
            $basketArray['itemSum']        = $basketArray['itemSumNet'];
            $basketArray['basketAmount']   = $basketArray['basketAmountNet'];
            $basketArray['shippingAmount'] = $basketArray['shippingAmountNet'];
        }

        // set basket information (amount, currency, orderId, ...)
        $this->heidelpayRequest['PRESENTATION_AMOUNT'] = $basketArray['basketAmount'];
        $this->heidelpayRequest['PRESENTATION_CURRENCY'] = $basketArray['currency'];

        $this->heidelpayRequest['FRONTEND_ENABLED']      = $methodInstance->needsCustomerInput() ? 'TRUE' : 'FALSE';
        $this->heidelpayRequest['FRONTEND_LANGUAGE']     = $this->sessionStorageFactory->getLocaleSettings()->language;
        $this->heidelpayRequest['FRONTEND_RESPONSE_URL'] = $this->urlService->generateURL(Routes::RESPONSE_URL);

        // add the origin domain, which is important for the CSP
        // set 'PREVENT_ASYNC_REDIRECT' to false, to ensure the customer is being redirected after submitting the form.
        if (\in_array($paymentMethod, self::CARD_METHODS, true)) {
            $this->heidelpayRequest['FRONTEND_PAYMENT_FRAME_ORIGIN'] = $this->urlService->getDomain();
            $this->heidelpayRequest['FRONTEND_PREVENT_ASYNC_REDIRECT'] = 'false';
        }

        if (isset($additionalParams['birthday'])) {
            $this->heidelpayRequest['NAME_BIRTHDATE']  = $additionalParams['birthday'];
        }

        if (isset($additionalParams['salutation'])) {
            $this->heidelpayRequest['NAME_SALUTATION']  = $additionalParams['salutation'];
        }

        if ($methodInstance->needsBasket()) {
            $this->heidelpayRequest['BASKET_ID'] = $this->basketService->requestBasketId($basket, $heidelpayAuth);
        }

        // shop + module information
        $this->heidelpayRequest['CRITERION_STORE_ID'] = $this->paymentHelper->getWebstoreId();
        $this->heidelpayRequest['CRITERION_MOP'] = $mopId;
        $this->heidelpayRequest['CRITERION_SHOP_TYPE'] = 'plentymarkets 7';
        $this->heidelpayRequest['CRITERION_SHOPMODULE_VERSION'] = Plugin::VERSION;
        $this->heidelpayRequest['CRITERION_BASKET_ID'] = $basketArray['id'];
        $this->heidelpayRequest['CRITERION_ORDER_ID'] = $basketArray['orderId'];
        $this->heidelpayRequest['CRITERION_ORDER_TIMESTAMP'] = $basketArray['orderTimestamp'];
        $this->heidelpayRequest['CRITERION_PUSH_URL'] = $this->urlService->generateURL(Routes::PUSH_NOTIFICATION_URL);

        $secret = $secretService->getSecretHash($transactionId);
        if (null !== $secret) {
            $this->heidelpayRequest['CRITERION_SECRET'] = $secret;
        }

        // general
        $this->heidelpayRequest['FRONTEND_CSS_PATH'] = $this->methodConfig->getIFrameCssPath($methodInstance);

        ksort($this->heidelpayRequest);
    }

    //<editor-fold desc="Handlers">
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

        if (!$response['isSuccess']) {
            throw new \RuntimeException($response['response']['PROCESSING.RETURN']);
        }

        // return rendered html content
        if ($type === GetPaymentMethodContent::RETURN_TYPE_HTML) {
            // return the payment frame url, if it is needed
            if (\array_key_exists('FRONTEND.PAYMENT_FRAME_URL', $response['response'])) {
                return $response['response']['FRONTEND.PAYMENT_FRAME_URL'];
            }

            return $response['response']['FRONTEND.REDIRECT_URL'];
        }

        // return the redirect url, if present.
        if ($type === GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL) {
            return $response['response']['FRONTEND.REDIRECT_URL'];
        }


        return $response;
    }

    /**
     * Handles the asynchronous response coming from the heidelpay API.
     *
     * @param array $post
     *
     * @return array
     */
    public function handlePaymentResponse(array $post): array
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
     * Create a plentymarkets payment entity.
     *
     * @param Transaction $txnData
     *
     * @return Payment
     *
     * @throws \RuntimeException
     */
    public function createOrGetPlentyPayment(Transaction $txnData): Payment
    {
        /** @var ArraySerializerService $serializer */
        $serializer = pluginApp(ArraySerializerService::class);

        // Payment might have already been created before (e.g. in response controller)
        $payment = $this->getPaymentByTransaction($txnData);
        if ($payment instanceof Payment) {
            return $payment;
        }

        // Create payment if it does not exist yet
        /** @var Payment $payment */
        $payment = pluginApp(Payment::class);
        $payment->mopId = $txnData->paymentMethodId;
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $payment->amount = $txnData->transactionDetails['PRESENTATION.AMOUNT'];
        $payment->currency = $txnData->transactionDetails['PRESENTATION.CURRENCY'];
        $payment->receivedAt = date('Y-m-d H:i:s');
        $payment->status = $this->paymentHelper->mapToPlentyStatus($txnData);
        $payment->type = Payment::PAYMENT_TYPE_CREDIT; // From Merchant point of view

        $bookingTextArray = ['Origin' => 'Heidelpay', 'TxnId' => $txnData->txnId, 'ShortId' => $txnData->shortId];
        $bookingText = $serializer->serializeKeyValue($bookingTextArray);
        $payment->properties = [
            $this->paymentHelper->newPaymentProperty(PaymentProperty::TYPE_ORIGIN, (string) Payment::ORIGIN_PLUGIN),
            $this->paymentHelper->newPaymentProperty(PaymentProperty::TYPE_TRANSACTION_ID, $txnData->txnId),
            $this->paymentHelper->newPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, $bookingText),
        ];

        $payment->regenerateHash = true;
        $this->notification->debug('payment.debugCreatePlentyPayment', __METHOD__, ['Payment' => $payment]);
        $payment = $this->paymentRepository->createPayment($payment);

        if (!$payment instanceof Payment) {
            throw new \RuntimeException('Heidelpay::error.errorDuringPaymentExecution');
        }

        return $payment;
    }

    /**
     * Attach plenty payment to plenty order (if it exists).
     *
     * @param Payment $payment
     * @param int $orderId
     * @throws \RuntimeException
     */
    public function assignPlentyPayment(Payment $payment, int $orderId)
    {
        try {
            $this->paymentHelper->assignPlentyPaymentToPlentyOrder($payment, $orderId);
        } catch (\RuntimeException $e) {
            $logData = ['Payment' => $payment, 'orderId' => $orderId];
            $this->notification->warning($e->getMessage(), __METHOD__, $logData);
            $this->paymentHelper->setBookingTextError($payment, $e->getMessage());
            throw new \RuntimeException('Heidelpay::error.errorDuringPaymentExecution');
        }

        $this->paymentHelper->removeBookingTextError($payment);
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

    /**
     * Creates transactionId and store it in the customer session to fetch the correct transaction later.
     *
     * @param Basket $basket
     * @return string
     */
    private function createNewTxnId(Basket $basket): string
    {
        $transactionId = $transactionId = uniqid($basket->id . '.', true);
        $this->sessionStorageFactory->getPlugin()->setValue(SessionKeys::SESSION_KEY_TXN_ID, $transactionId);
        return $transactionId;
    }

    /**
     * Fetch the payment for this transaction if it exists.
     *
     * @param Transaction $txnData
     * @return Payment|null
     */
    private function getPaymentByTransaction(Transaction $txnData)
    {
        /** @var ArraySerializerService $serializer */
        $serializer = pluginApp(ArraySerializerService::class);
        $txnId = $txnData->txnId;

        // check whether a payment has already been created for this transaction
        $payments = $this->paymentRepository->getPaymentsByPropertyTypeAndValue(
            PaymentProperty::TYPE_TRANSACTION_ID,
            $txnId
        );

        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $bookingText = $this->paymentHelper->getPaymentProperty($payment, PaymentProperty::TYPE_BOOKING_TEXT);
            $shortId = $serializer->deserializeKeyValue($bookingText->value)['ShortId'];

            if ($txnData->shortId === $shortId) {
                $logData = ['TxnData' => $txnData, 'Payment' => $payment];
                $this->notification->debug('payment.debugPaymentFound', __METHOD__, $logData);
                return $payment;
            }
        }
        return null;
    }

    //<editor-fold desc="Helpers">
    /**
     * Handles the given transaction by type
     *
     * @param Transaction $txn
     *
     * @throws \RuntimeException
     */
    public function handleTransaction(Transaction $txn)
    {
        switch ($txn->transactionType) {
            case TransactionType::DEBIT:             // intended fall-through
            case TransactionType::CAPTURE:           // intended fall-through
            case TransactionType::RECEIPT:
                $this->handleIncomingPayment($txn);
                break;
            case TransactionType::AUTHORIZE:         // intended fall-through
            case TransactionType::REGISTRATION:      // intended fall-through
            case TransactionType::CHARGEBACK:        // intended fall-through
            case TransactionType::CREDIT:            // intended fall-through
            case TransactionType::DEREGISTRATION:    // intended fall-through
            case TransactionType::FINALIZE:          // intended fall-through
            case TransactionType::INITIALIZE:        // intended fall-through
            case TransactionType::REBILL:            // intended fall-through
            case TransactionType::REFUND:            // intended fall-through
            case TransactionType::REREGISTRATION:    // intended fall-through
            case TransactionType::REVERSAL:          // intended fall-through
            default:
                // do nothing if the given Transaction needs no handling
                break;
        }
    }

    /**
     * Creates a payment if necessary and assigns it to the corresponding order.
     *
     * @param $txn
     *
     * @throws \RuntimeException
     */
    protected function handleIncomingPayment($txn)
    {
        $this->notification->debug('payment.debugHandleIncomingPayment', __METHOD__, ['Transaction' => $txn]);

        $relation = $this->orderTxnIdRepo->getOrderTxnIdRelationByTxnId($txn->txnId);
        if (!$relation instanceof OrderTxnIdRelation) {
            throw new \RuntimeException('response.errorOrderTxnIdRelationNotFound');
        }

        $payment = $this->createOrGetPlentyPayment($txn);
        $this->assignPlentyPayment($payment, $relation->orderId);
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

    //</editor-fold>
}
