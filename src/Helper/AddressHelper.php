<?php
/**
 * Provides for helper methods concerning addresses.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\helpers
 */
namespace Heidelpay\Helper;

use Plenty\Modules\Account\Address\Models\Address;

class AddressHelper
{
    /**
     * Returns street and house number as a single string.
     *
     * @param Address $address
     *
     * @return string
     */
    public function getStreetAndHno(Address $address): string
    {
        return $address->street . ' ' . $address->houseNumber;
    }
}
