<?php

namespace Heidelpay\Services;

use Heidelpay\Configs\MainConfigContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Item\Item\Models\Item;

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
class BasketService
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
     * BasketService constructor.
     * @param LibService $libraryService
     * @param MainConfigContract $config
     * @param ItemRepositoryContract $itemRepo
     * @param AuthHelper $authHelper
     */
    public function __construct(
        LibService $libraryService,
        MainConfigContract $config,
        ItemRepositoryContract $itemRepo,
        AuthHelper $authHelper
    ) {
        $this->libService = $libraryService;
        $this->config = $config;
        $this->authHelper = $authHelper;
        $this->itemRepo = $itemRepo;
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
}
