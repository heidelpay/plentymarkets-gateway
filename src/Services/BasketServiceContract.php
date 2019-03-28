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

use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
interface BasketServiceContract
{
    /**
     * Submits the Basket to the Basket-API and returns its ID.
     *
     * @param Basket $basket
     * @param array $authData
     *
     * @return string
     */
    public function requestBasketId(Basket $basket, array $authData): string;

    /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array.
     *
     * @return Address[]
     */
    public function getCustomerAddressData(): array;

    /**
     * Returns true if the billing address is B2B.
     */
    public function isBasketB2B(): bool;

    /**
     * Fetches current basket and returns it.
     *
     * @return Basket
     */
    public function getBasket(): Basket;

    /**
     * Returns the country code of the billing address as isoCode2.
     *
     * @return string
     */
    public function getBillingCountryCode(): string;

    /**
     * Returns true if the shipping and billing address are equal.
     */
    public function shippingMatchesBillingAddress(): bool;
}
