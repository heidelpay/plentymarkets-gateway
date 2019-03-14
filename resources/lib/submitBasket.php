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
    return (int)round(abs($value) * 100);
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
$basketAmount  = $basketData['basketAmount'];
$basketAmountNet = $basketData['basketAmountNet'];
$basketAmountVat = $basketAmount - $basketAmountNet;
$basket->setAmountTotalNet(normalizeValue($basketAmountNet))
       ->setCurrencyCode($basketData['currency'])
       ->setAmountTotalVat(normalizeValue($basketAmountVat));
$goodsAndShipmentNet = 0;

foreach ($basketItems as $item) {
    $basketItem = new BasketItem();
    $quantity   = $item['quantity'];
    $amount     = $item['price'] * $quantity;
    $vat        = $item['vat'];
    $amountNet  = $amount * 100 / (100 + $vat);
    $amountVat  = $amount - $amountNet; // $amountNet * $vat / 100;
    $basketItem->setAmountGross(normalizeValue($amount))
               ->setAmountVat(normalizeValue($amountVat))
               ->setAmountNet(normalizeValue($amountNet))
               ->setAmountDiscount(normalizeValue($item['rebate']))
               ->setQuantity($quantity)
               ->setVat($vat)
               ->setAmountPerUnit(normalizeValue($item['price']))
               ->setBasketItemReferenceId($item['id'])
               ->setTitle($item['title'])
               ->setType('goods');
    $basket->addBasketItem($basketItem);
    $goodsAndShipmentNet += $amountNet;
}

// Add shipping position
$shipping = new BasketItem();
$shippingAmount    = $basketData['shippingAmount'];
$shippingNet = $basketData['shippingAmountNet'];
$shippingVat = $shippingAmount - $shippingNet;
$shipping->setAmountGross(normalizeValue($shippingAmount))
         ->setAmountNet(normalizeValue($shippingNet))
         ->setAmountVat(normalizeValue($shippingVat))
         ->setQuantity(1)
         ->setAmountPerUnit(normalizeValue($shippingAmount))
         ->setBasketItemReferenceId('shipment')
         ->setTitle('Shipment')
         ->setType('shipment');
$basket->addBasketItem($shipping);
$goodsAndShipmentNet += $shippingNet;

// Add discount position
$discountAmount = $basketData['couponDiscount'];
if ($discountAmount !== 0) {
    $discountItem = new BasketItem();
    $discountNet  = $basketAmountNet - $goodsAndShipmentNet;
    $discountItem->setAmountGross(normalizeValue($discountAmount))
        ->setAmountNet(normalizeValue($discountNet))
        ->setQuantity(1)
        ->setAmountPerUnit(normalizeValue($discountAmount))
        ->setBasketItemReferenceId('discount')
        ->setTitle('Discount')
        ->setType('voucher');

    $basket->addBasketItem($discountItem);
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
