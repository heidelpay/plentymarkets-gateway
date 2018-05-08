<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Heidelpay\Constants\Routes;
use Heidelpay\Constants\Salutation;
use Heidelpay\Constants\TransactionStatus;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Heidelpay\Models\Contracts\TransactionRepositoryContract;
use Heidelpay\Models\Transaction;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
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
use Plenty\Plugin\ConfigRepository;
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

    /**
     * @var string
     */
    private $returnType;

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
     * PaymentService constructor.
     *
     * @param AddressRepositoryContract $addressRepository
     * @param CountryRepositoryContract $countryRepository
     * @param ConfigRepository $configRepository
     * @param LibService $libraryService
     * @param OrderRepositoryContract $orderRepository
     * @param PaymentOrderRelationRepositoryContract $paymentOrderRelationRepository
     * @param PaymentRepositoryContract $paymentRepository
     * @param TransactionRepositoryContract $transactionRepository
     * @param PaymentHelper $paymentHelper
     * @param Twig $twig
     */
    public function __construct(
        AddressRepositoryContract $addressRepository,
        CountryRepositoryContract $countryRepository,
        ConfigRepository $configRepository,
        LibService $libraryService,
        OrderRepositoryContract $orderRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepository,
        PaymentRepositoryContract $paymentRepository,
        TransactionRepositoryContract $transactionRepository,
        PaymentHelper $paymentHelper,
        Twig $twig
    ) {
        $this->addressRepository = $addressRepository;
        $this->countryRepository = $countryRepository;
        $this->libService = $libraryService;
        $this->orderRepository = $orderRepository;
        $this->paymentOrderRelationRepository = $paymentOrderRelationRepository;
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->paymentHelper = $paymentHelper;
        $this->twig = $twig;
    }

    /**
     * Returns the return type, which should be one of the following:
     * redirectUrl, externalContentUrl, htmlContent, errorCode, continue
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * @param string $type
     */
    private function setReturnType(string $type)
    {
        $this->getLogger(__METHOD__)->error('Setting return type', ['type' => $type]);
        $this->returnType = $type;
    }

    /**
     * Executes payment tasks after an order has been created.
     *
     * @param Basket         $basket
     * @param string         $paymentMethod
     * @param ExecutePayment $event
     *
     * @return string
     */
    public function executePayment(string $paymentMethod, Basket $basket, ExecutePayment $event): string
    {
        $this->getLogger(__METHOD__)->error('executePayment call', [
            'paymentMethod' => $paymentMethod,
            'basketId' => $basket->id,
            'mopId' => $event->getMop(),
            'orderId' => $event->getOrderId(),
            'eventType' => $event->getType(),
        ]);

        $amount = 0.0;
        $currency = 'EUR';
        $transactionDetails = [];
        $transaction = null;

        // todo: retrieve a heidelpay Transaction by basketId and paymentMethod-Id (Mop) to get values needed to create plenty payment (e.g. amount etc).
        $transactions = $this->transactionRepository->getTransactionsByBasketId($basket->id);
        $this->getLogger(__METHOD__)->error('Transactions', $transactions);
        $this->getLogger(__METHOD__)->debug('log.transactions', $transactions);
        $this->getLogger(__METHOD__)->debug('template.transactions', $transactions);
        foreach ($transactions as $transaction) {
            $this->getLogger(__METHOD__)->error('Transaction', $transaction);
            $allowedStatus = [TransactionStatus::ACK, TransactionStatus::PENDING];
            if (\in_array($transaction->status, $allowedStatus, false)) {
                $transactionDetails = $transaction->transactionDetails;
                break;
            }
        }

        if (!($transaction instanceof Transaction) ||
            !isset($transactionDetails['PRESENTATION.AMOUNT'], $transactionDetails['PRESENTATION.CURRENCY'])) {
            $this->setReturnType('error');
            return 'Error during payment execution!';
        }

        $plentyPayment = $this->createPlentyPayment($transaction, $transaction->paymentMethodId);
        if (!($plentyPayment instanceof Payment)) {
            $this->setReturnType('error');
            return 'Error during payment execution!';
        }

        $this->paymentHelper->assignPlentyPaymentToPlentyOrder($plentyPayment, $event->getOrderId());

        // todo: call createPlentyPayment and create the payment
        // todo: if the payment is an instance of Payment, link it to the order.
        // todo: ... if not, set returnType to 'error' and return a (user-friendly) message.

        $this->setReturnType('success');
        return 'Payment was successful!';
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     * @param int    $mopId
     *
     * @return array
     */
    private function sendGetPaymentMethodContentRequest(
        Basket $basket,
        string $paymentMethod,
        int $mopId
    ): array {
        $this->prepareRequest($basket, $paymentMethod, $mopId);

        $result = $this->libService->sendTransactionRequest($paymentMethod, [
            'request' => $this->heidelpayRequest,
            'transactionType' => TransactionType::AUTHORIZE // TODO: change depending on payment method & step.
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
     * @return string
     */
    public function getPaymentMethodContent(
        string $paymentMethod,
        Basket $basket,
        int $mopId
    ): string {
        $result = '';

        switch ($paymentMethod) {
            case CreditCard::class:
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_HTML);
                $result = $this->sendGetPaymentMethodContentRequest($basket, $paymentMethod, $mopId);
                break;

            case PayPal::class:
            case Sofort::class:
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL);
                $result = $this->sendGetPaymentMethodContentRequest($basket, $paymentMethod, $mopId);
                break;

            case Prepayment::class:
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_CONTINUE);
                break;

            default:
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                $result = 'Internal Error. Please try again later.';
                break;
        }

        if (\in_array($this->getReturnType(), [
            GetPaymentMethodContent::RETURN_TYPE_ERROR,
            GetPaymentMethodContent::RETURN_TYPE_CONTINUE
        ], true)) {
            return $result;
        }

        $this->getLogger(__METHOD__)->error('getPaymentMethodContent result', [$result]);

        if (\is_array($result)) {
            // return the exception message, if present.
            if (isset($result['exceptionCode'])) {
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                return $result['exceptionMsg'];
            }

            // return the iFrame url, if present.
            if ($this->getReturnType() === GetPaymentMethodContent::RETURN_TYPE_EXTERNAL_CONTENT_URL) {
                if (!$result['isSuccess']) {
                    $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                    return $result['response']['PROCESSING.RETURN'];
                }

                return $result['response']['FRONTEND.PAYMENT_FRAME_URL'];
            }

            // return rendered html content
            if ($this->getReturnType() === GetPaymentMethodContent::RETURN_TYPE_HTML) {
                if (!$result['isSuccess']) {
                    $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                    return $result['response']['PROCESSING.RETURN'];
                }

                $urlKey = $paymentMethod === CreditCard::class ? 'FRONTEND.PAYMENT_FRAME_URL' : 'FRONTEND.REDIRECT_URL';

                $this->getLogger(__METHOD__)->error('html return urlKey', [
                    'urlKey' => $urlKey,
                    'value' => $result['response'][$urlKey]
                ]);

                return $this->twig->render('heidelpay::externalCardForm', [
                    'paymentFrameUrl' => $result['response'][$urlKey]
                ]);
            }

            // return the redirect url, if present.
            if ($this->getReturnType() === GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL) {
                if (!$result['isSuccess']) {
                    $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                    return $result['response']['PROCESSING.RETURN'];
                }

                return $result['response']['FRONTEND.REDIRECT_URL'];
            }
        }

        return $result;
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

        // set basket information (amount, currency, orderId, ...)
        $this->heidelpayRequest['PRESENTATION_AMOUNT'] = $basket->basketAmount;
        $this->heidelpayRequest['PRESENTATION_CURRENCY'] = $basket->currency;
        $this->heidelpayRequest['IDENTIFICATION_TRANSACTIONID'] = $basket->id;

        // TODO: receive frontend language somehow.
        $this->heidelpayRequest['FRONTEND_ENABLED'] = $this->paymentHelper->getFrontendEnabled($paymentMethod);
        $this->heidelpayRequest['FRONTEND_LANGUAGE'] = 'DE';
        $this->heidelpayRequest['FRONTEND_RESPONSE_URL'] =
            $this->paymentHelper->getDomain() . '/' . Routes::RESPONSE_URL;

        // add the origin domain, which is important for the CSP
        // set 'PREVENT_ASYNC_REDIRECT' to false, to ensure the customer is being redirected after submitting the form.
        if ($paymentMethod === CreditCard::class) {
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
        $this->heidelpayRequest['CRITERION_PUSH_URL'] =
            $this->paymentHelper->getDomain() . '/' . Routes::PUSH_NOTIFICATION_URL;

        // TODO: Riskinformation for future payment methods

        $this->getLogger(__METHOD__)->error('prepareRequest', $this->heidelpayRequest);
    }

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
        /** @var Payment $payment */
        $payment = pluginApp(Payment::class);

        $payment->mopId = $paymentMethodId;
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $paymentDetails = $paymentData->transactionDetails;
        $payment->status = $this->paymentHelper->mapToPlentyStatus($paymentDetails);
        $payment->amount = $paymentDetails['PRESENTATION.AMOUNT'];
        $payment->currency = $paymentDetails['PRESENTATION.CURRENCY'];
        $payment->receivedAt = $paymentData->createdAt;
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
//        $paymentProperty[] = $this->paymentHelper->getPaymentProperty(
//            PaymentProperty::,
//            Payment::ORIGIN_PLUGIN
//        );

        // create the payment
        $payment->properties = $paymentProperty;
        $payment->regenerateHash = true;
        $payment = $this->paymentRepository->createPayment($payment);

        return $payment;
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
}
