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

use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request as BasketApiRequest;

function normalizeValue($value) {
    return (int)round($value * 100);
}

/** @var array $authData */
$authData = SdkRestApi::getParam('auth');
/** @var array $basketData */
$basketData = SdkRestApi::getParam('basket');
/** @var array $basketItems */
$basketItems = SdkRestApi::getParam('basketItems');
/** @var bool $sandmoxmode */
$sandboxmode = SdkRestApi::getParam('sandboxmode');

$basket = new \Heidelpay\PhpBasketApi\Object\Basket();
$basket->setAmountTotalNet(normalizeValue($basketData['basketAmountNet']));
$basket->setAmountTotalDiscount(normalizeValue($basketData['basketRebate']));
$basket->setCurrencyCode($basketData['currency']);
$basketAmountVat = 0;

foreach ($basketItems as $item) {
    $basketItem = new BasketItem();
    $quantity   = $item['quantity'];
    $amount     = $item['price'] * $quantity;
    $vat        = $item['vat'];
    $amountVat  = $amount * $vat;
    $basketItem->setAmountGross(normalizeValue($amount));
    $basketItem->setAmountVat(normalizeValue($amountVat));
    $basketItem->setAmountNet(normalizeValue($amount * 100 / (100 + $vat)));
    $basketItem->setAmountDiscount(normalizeValue($item['rebate']));
    $basketItem->setQuantity($quantity);
    $basketItem->setVat($vat);
    $basketItem->setAmountPerUnit(normalizeValue($item['price']));
    $basketItem->setBasketItemReferenceId($item['id']);
    $basketItem->setTitle($item['title']);
    $basket->addBasketItem($basketItem);

    $basketAmountVat += $amountVat;
}
$basket->setAmountTotalVat(normalizeValue($basketAmountVat));

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
