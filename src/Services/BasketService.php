<?php
/**
 * Provides connection to heidelpay basketApi.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\services
 */
namespace Heidelpay\Services;

use Heidelpay\Configs\MainConfigContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Item\Item\Models\Item;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;

class BasketService implements BasketServiceContract
{
    /** @var LibService */
    private $libService;

    /** @var MainConfigContract */
    private $config;

    /** @var AuthHelper */
    private $authHelper;

    /** @var ItemRepositoryContract */
    private $itemRepo;

    /** @var AddressRepositoryContract */
    private $addressRepo;

    /** @var BasketRepositoryContract */
    private $basketRepo;

    /** @var CountryRepositoryContract */
    private $countryRepository;

    /**
     * BasketService constructor.
     *
     * @param CountryRepositoryContract $countryRepository
     * @param AddressRepositoryContract $addressRepository
     * @param BasketRepositoryContract $basketRepo
     * @param LibService $libraryService
     * @param MainConfigContract $config
     * @param ItemRepositoryContract $itemRepo
     * @param AuthHelper $authHelper
     */
    public function __construct(
        CountryRepositoryContract $countryRepository,
        AddressRepositoryContract $addressRepository,
        BasketRepositoryContract $basketRepo,
        LibService $libraryService,
        MainConfigContract $config,
        ItemRepositoryContract $itemRepo,
        AuthHelper $authHelper
    ) {
        $this->libService          = $libraryService;
        $this->config              = $config;
        $this->authHelper          = $authHelper;
        $this->itemRepo            = $itemRepo;
        $this->addressRepo         = $addressRepository;
        $this->basketRepo          = $basketRepo;
        $this->countryRepository   = $countryRepository;
    }

    /**
     * Submits the Basket to the Basket-API and returns its ID.
     *
     * @param Basket $basket
     * @param array  $authData
     *
     * @return string
     */
    public function requestBasketId(Basket $basket, array $authData): string
    {
        $params = [];
        $params['auth'] = [
            'login' => $authData['USER_LOGIN'],
            'password' => $authData['USER_PWD'],
            'senderId' => $authData['SECURITY_SENDER']
        ];
        $params['basket'] = $basket->toArray();

        $items = [];
        foreach ($basket->basketItems as $basketItem) {
            /** @var BasketItem $basketItem */
            /** @var Item $item */
            $item    = $this->authHelper->processUnguarded(
                function () use ($basketItem) {
                    return $this->itemRepo->show($basketItem->itemId);
                }
            );
            $itemArray = $basketItem->toArray();
            $itemArray['title'] = $item->texts[0]->name1;
            $items[] = $itemArray;
        }

        $params['basketItems'] = $items;
        $params['sandboxmode'] = $this->config->isInSandboxMode();
        $response = $this->libService->submitBasket($params);
        return $response['basketId'];
    }

    /**
     * {@inheritDoc}
     */
    public function shippingMatchesBillingAddress(): bool
    {
        $basket = $this->getBasket();
        if ($basket->customerShippingAddressId === null || $basket->customerShippingAddressId === -99) {
            return true;
        }

        $addresses = $this->getCustomerAddressData();
        $billingAddress = $addresses['billing']->toArray();
        $shippingAddress = $addresses['shipping']->toArray();

        return  $billingAddress['gender'] === $shippingAddress['gender'] &&
                $this->strCompare($billingAddress['address1'], $shippingAddress['address1']) &&
                $this->strCompare($billingAddress['address2'], $shippingAddress['address2']) &&
                $billingAddress['postalCode'] === $shippingAddress['postalCode'] &&
                $this->strCompare($billingAddress['town'], $shippingAddress['town']) &&
                $this->strCompare($billingAddress['countryId'], $shippingAddress['countryId']) &&
                (
                    ($this->isBasketB2B()  && $this->strCompare($billingAddress['name1'], $shippingAddress['name1'])) ||
                    (!$this->isBasketB2B() && $this->strCompare($billingAddress['name2'], $shippingAddress['name2'])
                                           && $this->strCompare($billingAddress['name3'], $shippingAddress['name3']))
                );
    }
        /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array.
     *
     * @return Address[]
     */
    public function getCustomerAddressData(): array
    {
        $basket = $this->getBasket();

        $addresses            = [];
        $invoiceAddressId     = $basket->customerInvoiceAddressId;
        $addresses['billing'] = empty($invoiceAddressId) ? null : $this->getAddressById($invoiceAddressId);

        // if the shipping address is -99 or null, it is matching the billing address.
        $shippingAddressId = $basket->customerShippingAddressId;
        if (empty($shippingAddressId) || $shippingAddressId === -99) {
            $addresses['shipping'] = $addresses['billing'];
        } else {
            $addresses['shipping'] = $this->getAddressById($shippingAddressId);
        }

        return $addresses;
    }

    /**
     * Returns true if the billing address is B2B.
     *
     * @return bool
     */
    public function isBasketB2B(): bool
    {
        $billingAddress = $this->getCustomerAddressData()['billing'];
        return $billingAddress ? $billingAddress->gender === null : false;
    }

    /**
     * Fetches current basket and returns it.
     *
     * @return Basket
     */
    public function getBasket(): Basket
    {
        return $this->basketRepo->load();
    }

    /**
     * {@inheritDoc}
     */
    public function getBillingCountryCode(): string
    {
        $billingAddress = $this->getCustomerAddressData()['billing'];
        return $billingAddress ?
            $this->countryRepository->findIsoCode($billingAddress->countryId, 'isoCode2') : '';
    }

    /**
     * Returns true if the strings match case insensitive.
     *
     * @param string $string1
     * @param string $string2
     * @return bool
     */
    private function strCompare($string1, $string2): bool
    {
        $symbols = [' ', '-', '.', '(', ')'];
        $normalizedString1 = str_replace($symbols, '', strtolower(trim($string1)));
        $normalizedString2 = str_replace($symbols, '', strtolower(trim($string2)));

        $specialChars = ['ä', 'ü', 'ö', 'ß'];
        $specialCharReplacements = ['ae', 'ue', 'oe', 'ss'];
        $normalizedString1 = str_replace($specialChars, $specialCharReplacements, $normalizedString1);
        $normalizedString2 = str_replace($specialChars, $specialCharReplacements, $normalizedString2);

        $normalizedString1 = str_replace('strasse', 'str', $normalizedString1);
        $normalizedString2 = str_replace('strasse', 'str', $normalizedString2);

        return $normalizedString1 === $normalizedString2;
    }

    /**
     * @param $addressId
     * @return Address|null
     */
    private function getAddressById($addressId)
    {
        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);
        $address = $authHelper->processUnguarded(
            function () use ($addressId) {
                return $this->addressRepo->findAddressById($addressId);
            }
        );
        return $address;
    }
}
