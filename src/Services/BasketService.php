<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/${Package}
 */
namespace Heidelpay\Services;

use Heidelpay\Configs\MainConfigContract;
use Plenty\Modules\Basket\Models\Basket;

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
     * BasketService constructor.
     * @param LibService $libraryService
     * @param MainConfigContract $config
     */
    public function __construct(
        LibService $libraryService,
        MainConfigContract $config
    ) {
        $this->libService = $libraryService;
        $this->config = $config;
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
        $params['sandboxmode'] = $this->config->isInSandboxMode();

        $response = $this->libService->submitBasket($params);
        return $response['basketId'];
    }
}
