<?php
/**
 * Provides service methods for Order instances.
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

use Plenty\Modules\Order\Models\Order;
use RuntimeException;

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
     * @throws RuntimeException
     */
    public function getOrder(int $orderId): Order;

    /**
     * Returns the order object corresponding to the given txnId.
     *
     * @param $txnId
     * @return Order
     * @throws RuntimeException
     */
    public function getOrderByTxnId($txnId): Order;
}
