<?php

namespace Heidelpay\Services;

use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\Routes;
use Heidelpay\Constants\Salutation;
use Heidelpay\Constants\SessionKeys;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Heidelpay\Traits\Translator;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
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
    use Translator;

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
    private $config;

    /**
     * PaymentService constructor.
     *
     * @param AddressRepositoryContract $addressRepository
     * @param CountryRepositoryContract $countryRepository
     * @param LibService $libraryService
     * @param PaymentRepositoryContract $paymentRepository
     * @param TransactionRepositoryContract $transactionRepo
     * @param PaymentHelper $paymentHelper
     * @param Twig $twig
     * @param FrontendSessionStorageFactoryContract $sessionStorageFac
     * @param NotificationServiceContract $notification
     * @param MethodConfigContract $config
     */
    public function __construct(
        AddressRepositoryContract $addressRepository,
        CountryRepositoryContract $countryRepository,
        LibService $libraryService,
        PaymentRepositoryContract $paymentRepository,
        TransactionRepositoryContract $transactionRepo,
        PaymentHelper $paymentHelper,
        Twig $twig,
        FrontendSessionStorageFactoryContract $sessionStorageFac,
        NotificationServiceContract $notification,
        MethodConfigContract $config
    ) {
        $this->addressRepository = $addressRepository;
        $this->countryRepository = $countryRepository;
        $this->libService = $libraryService;
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepo;
        $this->paymentHelper = $paymentHelper;
        $this->twig = $twig;
        $this->sessionStorageFactory = $sessionStorageFac;
        $this->notification = $notification;
        $this->config = $config;
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
        $orderId = $event->getOrderId();
        $mopId = $event->getMop();

        $logData = ['paymentMethod' => $paymentMethod, 'mopId' => $mopId, 'orderId' => $orderId];
        $this->notification->debug('payment.debugExecutePayment', __METHOD__, $logData);

        $transactionDetails = [];
        $transaction = null;

        // Retrieve heidelpay Transaction by txnId to get values needed for plenty payment (e.g. amount etc).
        $txnId = $this->sessionStorageFactory->getPlugin()->getValue(SessionKeys::SESSION_KEY_TXN_ID);
        $this->paymentHelper->createOrUpdateRelation($txnId, $mopId, $orderId);

        $transactions = $this->transactionRepository->getTransactionsByTxnId($txnId);
        foreach ($transactions as $transaction) {
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

        // create payment transaction if type is not authorize, in this case it will be created if a CP is pushed.
        if (TransactionType::AUTHORIZE !== $transaction->transactionType) {
            try {
                $this->createPlentyPayment($transaction, $transaction->paymentMethodId, $orderId);
            } catch (\RuntimeException $e) {
                return ['error', $e->getMessage()];
            }
        }

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

        $txnId = $this->createNewTxnId($basket);
        $this->paymentHelper->createOrUpdateRelation($txnId, $mopId);
        $this->prepareRequest($basket, $paymentMethod, $mopId, $txnId);

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

        $clientErrorMessage = 'heidelpay::payment.errorInternalErrorTryAgainLater';

        /** @var AbstractMethod $methodInstance */
        $methodInstance = $this->paymentHelper->getPaymentMethodInstance($paymentMethod);

        if (!$methodInstance instanceof PaymentMethodContract) {
            $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
            $value = $clientErrorMessage;
            return [$type, $value];
        }

        $type = $methodInstance->getReturnType();

        if ($type === GetPaymentMethodContent::RETURN_TYPE_CONTINUE) {
            return [$type, $value];
        }

        if ($methodInstance->hasToBeInitialized()) {
            $result = $this->sendPaymentRequest($basket, $paymentMethod, $methodInstance->getTransactionType(), $mopId);
            try {
                $value = $this->handleSyncResponse($type, $result);
            } catch (\RuntimeException $e) {
                $this->notification->error($clientErrorMessage, __METHOD__, [$type, $e->getMessage()]);
                $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;
                $value = $this->getTranslator()->trans($clientErrorMessage);
            }

            if ($type === GetPaymentMethodContent::RETURN_TYPE_HTML) {
                // $value should contain the payment frame url (also form url)
                $value = $this->renderPaymentForm($methodInstance->getFormTemplate(), ['paymentFrameUrl' => $value]);
            }
        }

        return [$type, $value];
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
     * @param Basket $basket
     * @param string $paymentMethod
     * @param int $mopId
     * @param string $transactionId
     */
    private function prepareRequest(Basket $basket, string $paymentMethod, int $mopId, string $transactionId)
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

        // general
        $methodInstance = $this->paymentHelper->getPaymentMethodInstance($paymentMethod);
        if (null !== $methodInstance) {
            $this->heidelpayRequest['FRONTEND_CSS_PATH'] = $this->config->getIFrameCssPath($methodInstance);
        }

        // TODO: Riskinformation for future payment methods
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
     * @param Transaction $txnData
     * @param int $paymentMethodId
     *
     * @param int $orderId
     * @return Payment
     */
    public function createPlentyPayment(Transaction $txnData, int $paymentMethodId, int $orderId): Payment
    {
        $paymentDetails = $txnData->transactionDetails;
        $txnId = $txnData->txnId;

        /** @var Payment $payment */
        $payment = pluginApp(Payment::class);
        $payment->mopId = $paymentMethodId;
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $payment->amount = $paymentDetails['PRESENTATION.AMOUNT'];
        $payment->currency = $paymentDetails['PRESENTATION.CURRENCY'];
        $payment->receivedAt = date('Y-m-d H:i:s');
        $payment->status = $this->paymentHelper->mapToPlentyStatus($txnData);
        $payment->type = Payment::PAYMENT_TYPE_CREDIT; // From Merchant point of view

        $properties = [];
        $properties[] = $this->paymentHelper
            ->getPaymentProperty(PaymentProperty::TYPE_ORIGIN, (string) Payment::ORIGIN_PLUGIN);
        $properties[] = $this->paymentHelper->getPaymentProperty(PaymentProperty::TYPE_TRANSACTION_ID, $txnId);
        $bookingText = 'Heidelpay Txn-ID: ' . $txnId;
        $properties[] = $this->paymentHelper->getPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, $bookingText);
        $payment->properties = $properties;

        // create the payment
        $payment->regenerateHash = true;
        $this->notification->debug('payment.debugCreatePlentyPayment', __METHOD__, ['Payment' => $payment]);
        $payment = $this->paymentRepository->createPayment($payment);

        if (!$payment instanceof Payment) {
            throw new \RuntimeException('heidelpay::error.errorDuringPaymentExecution');
        }

        try {
            $this->paymentHelper->assignPlentyPaymentToPlentyOrder($payment, $orderId);
        } catch (\RuntimeException $e) {
            $logData = ['Payment' => $payment, 'txnId' => $txnId];
            $this->notification->error($e->getMessage(), __METHOD__, $logData);
            // todo: Enable when plenty fix exists.
//            $this->paymentHelper->prependPaymentBookingText($payment, $e->getMessage());
            throw new \RuntimeException('heidelpay::error.errorDuringPaymentExecution');
        }

        return $payment;
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
}
