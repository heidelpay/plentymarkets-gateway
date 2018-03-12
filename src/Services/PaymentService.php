<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

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
    private $heidelpayRequest;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;

    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepository;

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

    public function __construct(
        AddressRepositoryContract $addressRepository,
        CountryRepositoryContract $countryRepository,
        ConfigRepository $configRepository,
        LibService $libraryService,
        PaymentMethodRepositoryContract $paymentMethodRepository,
        PaymentRepositoryContract $paymentRepository,
        PaymentHelper $paymentHelper
    ) {
        $this->addressRepository = $addressRepository;
        $this->countryRepository = $countryRepository;
        $this->configRepository = $configRepository;
        $this->libService = $libraryService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paymentHelper = $paymentHelper;
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
        $this->getLogger(__METHOD__)->error('Setting return type', [
            'type' => $type
        ]);

        $this->returnType = $type;
    }

    /**
     * @param Basket $basket
     * @param $paymentMethod
     *
     * @return string
     */
    public function executePayment(Basket $basket, $paymentMethod): string
    {
        $this->prepareRequest($basket, $paymentMethod);

        $response = $this->libService->sendTransactionRequest($paymentMethod, $this->heidelpayRequest);

        if (isset($response['exceptionCode'])) {
            $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
            $returnValue = $response['exceptionMsg'];
        } else {
            $returnValue = $response['FRONTEND.REDIRECT_URL'];
            $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL);
        }

        return $returnValue;
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     *
     * @return array
     */
    private function sendGetPaymentMethodContentRequest(Basket $basket, string $paymentMethod): array
    {
        $this->prepareRequest($basket, $paymentMethod);

        $result = $this->libService->sendTransactionRequest($paymentMethod, [
            'request' => $this->heidelpayRequest,
            'transactionType' => TransactionType::AUTHORIZE // TODO: change depending on payment method & step.
        ]);

        $this->getLogger(__METHOD__)->error('GetPaymentMethodContent result', $result);

        return $result;
    }

    /**
     * Depending on the given payment method, return some information
     * (or just continue) regarding the payment method.
     *
     * @param string $paymentMethod
     * @param Basket $basket
     *
     * @return string
     */
    public function getPaymentMethodContent(string $paymentMethod, Basket $basket): string
    {
        $result = '';

        switch ($paymentMethod) {
            case CreditCard::class:
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_EXTERNAL_CONTENT_URL);
                $result = $this->sendGetPaymentMethodContentRequest($basket, $paymentMethod);
                break;

            case PayPal::class:
            case Sofort::class:
                $this->getLogger(__METHOD__)->error('PayPal/Sofort case');
                $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL);
                $result = $this->sendGetPaymentMethodContentRequest($basket, $paymentMethod);
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
                    return $result['PROCESSING_REASON'];
                }

                return $result['FRONTEND_PAYMENT_FRAME_URL'];
            }

            // return the redirect url, if present.
            if ($this->getReturnType() === GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL) {
                if (!$result['isSuccess']) {
                    $this->setReturnType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                    return $result['PROCESSING_REASON'];
                }

                return $result['FRONTEND_REDIRECT_URL'];
            }
        }

        return $result;
    }

    /**
     * @param Basket $basket
     * @param string $paymentMethod
     */
    private function prepareRequest(Basket $basket, string $paymentMethod)
    {
        // set authentification data
        $heidelpayAuth = $this->paymentHelper->getHeidelpayAuthenticationConfig($paymentMethod);
        $this->heidelpayRequest = array_merge($this->heidelpayRequest, $heidelpayAuth);

        // set customer personal information & address data
        $addresses = $this->getCustomerAddressData($basket);
        $this->heidelpayRequest['IDENTIFICATION.SHOPPERID'] = $basket->customerId;
        $this->heidelpayRequest['NAME.GIVEN'] = $addresses['billing']->firstName;
        $this->heidelpayRequest['NAME.FAMILY'] = $addresses['billing']->lastName;
        $this->heidelpayRequest['CONTACT.EMAIL'] = $addresses['billing']->email;
        $this->heidelpayRequest['ADDRESS.STREET'] = $this->getFullStreetAndHouseNumber($addresses['billing']);
        $this->heidelpayRequest['ADDRESS.ZIP'] = $addresses['billing']->postalCode;
        $this->heidelpayRequest['ADDRESS.CITY'] = $addresses['billing']->town;
        $this->heidelpayRequest['ADDRESS.COUNTRY'] = $this->countryRepository->findIsoCode(
            $addresses['billing']->countryId,
            'isoCode2'
        );

        if ($addresses['billing']->companyName !== null) {
            $this->heidelpayRequest['NAME.COMPANY'] = $addresses['billing']->companyName;
        }

        // set basket information (amount, currency, orderId, ...)
        $this->heidelpayRequest['PRESENTATION.AMOUNT'] = $basket->basketAmount;
        $this->heidelpayRequest['PRESENTATION.CURRENCY'] = $basket->currency;
        $this->heidelpayRequest['IDENTIFICATION.TRANSACTIONID'] = $basket->orderId;

        // TODO: receive frontend language somehow.
        $this->heidelpayRequest['FRONTEND.ENABLED'] = 'TRUE';
        $this->heidelpayRequest['FRONTEND.LANGUAGE'] = 'DE';
        $this->heidelpayRequest['FRONTEND.RESPONSE_URL'] = $this->paymentHelper->getDomain() . '/heidelpay/response';

        // TODO: Secure information for B2C payment methods
        if (false) {
            $this->heidelpayRequest['NAME.SALUTATION'] = $addresses['billing']->gender === 'male' ? 'MR' : 'MRS';
            $this->heidelpayRequest['NAME.BIRTHDATE'] = $addresses['billing']->birthday;
            $this->heidelpayRequest['BASKET.ID'] = $this->getBasketId($basket, $heidelpayAuth);
        }

        $this->getLogger(__METHOD__)->error('prepareRequest', $this->heidelpayRequest);

        // TODO: Riskinformation for future payment methods
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
            'login' => $authData['USER.LOGIN'],
            'password' => $authData['USER.PWD'],
            'senderId' => $authData['SECURITY.SENDER'],
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
}
