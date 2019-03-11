<?php
/**
 * Sends requests to heidelpay basketApi.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\external-lib-callbacks
 */

use Heidelpay\PhpBasketApi\Request as BasketApiRequest;

/** @var array $authData */
$authData = SdkRestApi::getParam('auth');
/** @var array $basketData */
$basketData = SdkRestApi::getParam('basket');
/** @var array $basketItems */
$basketItems = SdkRestApi::getParam('basketItems');
/** @var bool $sandmoxmode */
$sandboxmode = SdkRestApi::getParam('sandboxmode');

$basket = new \Heidelpay\PhpBasketApi\Object\Basket();
$basket->setAmountTotalNet((int) $basketData['basketAmountNet'] * 100);
$basket->setAmountTotalDiscount((int) $basketData['basketRebate'] * 100);
$basket->setCurrencyCode($basketData['currency']);

foreach ($basketItems as $item) {
    $basketItem = new \Heidelpay\PhpBasketApi\Object\BasketItem();
    $basketItem->setAmountGross((int)$item['price'] * 100);
    $basketItem->setAmountDiscount((int)$item['rebate'] * 100);
    $basketItem->setQuantity((int)$item['quantity']);
    $basketItem->setVat((int)$item['vat'] * 100);
    $basket->addBasketItem($basketItem);
}

$request = new BasketApiRequest();
$request->setAuthentication($authData['login'], $authData['password'], $authData['senderId']);
$request->setIsSandboxMode($sandboxmode);
$request->setBasket($basket);

// submit the basket via api call.
$response = $request->addNewBasket();

return [
    'basketId' => $response->getBasketId(),
    'isSuccess' => $response->isSuccess(),
    'resultMsg' => $response->printMessage()
];
