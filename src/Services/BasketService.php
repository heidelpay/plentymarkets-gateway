<?php

namespace Heidelpay\Services;

use Heidelpay\Configs\MainConfigContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Models\BasketItem;

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
     * @var NotificationServiceContract
     */
    private $notificationService;

    /**
     * BasketService constructor.
     * @param LibService $libraryService
     * @param MainConfigContract $config
     * @param NotificationServiceContract $notificationService
     */
    public function __construct(
        LibService $libraryService,
        MainConfigContract $config,
        NotificationServiceContract $notificationService
    ) {
        $this->libService = $libraryService;
        $this->config = $config;
        $this->notificationService = $notificationService;
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
        $this->notificationService->error('Vor BasketItems loop', __METHOD__);
        foreach ($basket->basketItems as $item) {
            /** @var BasketItem $item*/
            $this->notificationService->error('BasketItem', __METHOD__, [
                'item' => $item->toArray(),
                'itemObj' => $item,
                'items' => $basket->basketItems
            ]);
            $items[] = $item->toArray();
        }
        $this->notificationService->error('Nach BasketItems llop', __METHOD__);
        $params['basketItems'] = $items;

        $params['sandboxmode'] = $this->config->isInSandboxMode();

        $response = $this->libService->submitBasket($params);

        $this->notificationService->error('BasketItems', __METHOD__, ['basket' => $basket, 'array' => $basket->toArray(), 'items' => $basket->basketItems, 'itemsArrays' => $items]);

        return $response['basketId'];
    }
}
