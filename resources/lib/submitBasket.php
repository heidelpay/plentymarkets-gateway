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
/** @var bool $sandmoxmode */
$sandboxmode = SdkRestApi::getParam('sandboxmode');

$basket = new \Heidelpay\PhpBasketApi\Object\Basket();
$basket->setAmountTotalNet((int) $basketData['basketAmountNet'] * 100);
//$basket->setAmountTotalVat($basketData['??']);
$basket->setAmountTotalDiscount((int) $basketData['basketRebate'] * 100);

foreach ($basketData['basketItems'] as $cartItem) {
    $basketItem = new \Heidelpay\PhpBasketApi\Object\BasketItem();
    $basketItem->setAmountGross((int) $cartItem['basketAmount'] * 100);
    $basketItem->setAmountNet((int) $cartItem['basketAmountNet'] * 100);
    $basketItem->setAmountDiscount((int) $cartItem['rebate'] * 100);
    $basketItem->setQuantity((int) $cartItem['quantity'] * 100);
    $basketItem->setVat((int) $cartItem['vat'] * 100);
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
