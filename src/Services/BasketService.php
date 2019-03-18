<?php

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
class BasketService implements BasketServiceContract
{
    /**
     * @var LibService
     */
    private $libService;
    /**
     * @var MainConfigContract
     */
    private $config;
    /**
     * @var AuthHelper
     */
    private $authHelper;
    /**
     * @var ItemRepositoryContract
     */
    private $itemRepo;
    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;
    /**
     * @var BasketRepositoryContract
     */
    private $basketRepo;
    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * BasketService constructor.
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
        $this->libService = $libraryService;
        $this->config = $config;
        $this->authHelper = $authHelper;
        $this->itemRepo = $itemRepo;
        $this->addressRepository = $addressRepository;
        $this->basketRepo = $basketRepo;
        $this->countryRepository = $countryRepository;
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
            'senderId' => $authData['SECURITY_SENDER'],
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
        $billingAddress = $addresses['billing'];
        $shippingAddress = $addresses['shipping'];

        return  $billingAddress->gender === $shippingAddress->gender &&
                strcasecmp($billingAddress->address1, $shippingAddress->address1) &&
                strcasecmp($billingAddress->address2, $shippingAddress->address2) &&
                $billingAddress->postalCode === $shippingAddress->postalCode &&
                (
                    ($this->isBasketB2B()  && strcasecmp($billingAddress->name1, $shippingAddress->name1)) ||
                    (!$this->isBasketB2B() && strcasecmp($billingAddress->name2, $shippingAddress->name2)
                                           && strcasecmp($billingAddress->name3, $shippingAddress->name3))
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

        $addresses = [];
        $addresses['billing'] = $basket->customerInvoiceAddressId ?
            $this->addressRepository->findAddressById($basket->customerInvoiceAddressId) : null;

        // if the shipping address is -99 or null, it is matching the billing address.
        if ($basket->customerShippingAddressId === null || $basket->customerShippingAddressId === -99) {
            $addresses['shipping'] = $addresses['billing'];
            return $addresses;
        }

        $addresses['shipping'] = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
        return $addresses;
    }

    /**
     * Returns true if the billing address is B2B.
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
}
