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

use Plenty\Modules\Order\Models\Order;

interface OrderServiceContract
{
    /**
     * Returns the language code of the given order or 'DE' as default.
     *
     * @param Order $order
     * @return string
     */
    public function getLanguage(Order $order): string;

    /**
     * Fetches the Order object to the given orderId.
     *
     * @param int $orderId
     * @return Order
     * @throws \RuntimeException
     */
    public function getOrder(int $orderId): Order;
}
