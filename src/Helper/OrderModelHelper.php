<?php
/**
 * Provides for helper methods concerning plenty order model.
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

use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;

class OrderModelHelper
{
    /**
     * Returns the language code of the given order or 'DE' as default.
     *
     * @param Order $order
     * @return string
     */
    public function getLanguage(Order $order): string
    {
        return $this->getOrderProperty($order, OrderPropertyType::DOCUMENT_LANGUAGE) ?? 'DE';
    }

    /**
     * Returns the mopId of the given order or '' as default.
     *
     * @param Order $order
     * @return string
     */
    public function getMopId(Order $order): string
    {
        return $this->getOrderProperty($order, OrderPropertyType::PAYMENT_METHOD) ?? '';
    }

    /**
     * Returns the txnId of the given order or '' as default.
     *
     * @param Order $order
     * @return string
     */
    public function getTxnId(Order $order): string
    {
        return $this->getOrderProperty($order, OrderPropertyType::EXTERNAL_ORDER_ID) ?? '';
    }

    /**
     * Returns the order property identified by the given typeId or null if it cannot be found.
     *
     * @param Order $order
     * @param $typeId
     * @return string|null
     */
    private function getOrderProperty(Order $order, $typeId)
    {
        /** @var OrderProperty $property */
        foreach ($order->properties as $property) {
            if ($property->typeId === $typeId) {
                return $property->value;
            }
        }

        return null;
    }
}
