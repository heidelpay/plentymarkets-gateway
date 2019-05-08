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

use Exception;
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use RuntimeException;

class OrderService implements OrderServiceContract
{
    /** @var OrderRepositoryContract */
    private $orderRepo;

    /** @var OrderTxnIdRelationRepositoryContract */
    private $orderTxnIdRelationRepo;

    /**
     * OrderHelper constructor.
     * @param OrderRepositoryContract $orderRepository
     * @param OrderTxnIdRelationRepositoryContract $orderTxnIdRelationRepo
     */
    public function __construct(
        OrderRepositoryContract $orderRepository,
        OrderTxnIdRelationRepositoryContract $orderTxnIdRelationRepo
    ) {
        $this->orderRepo              = $orderRepository;
        $this->orderTxnIdRelationRepo = $orderTxnIdRelationRepo;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder(int $orderId): Order
    {
        $order = null;

        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);

        try {// Get the order by the given order ID
            $order = $authHelper->processUnguarded(
                function () use ($orderId) {
                    return $this->orderRepo->findOrderById($orderId, ['comments']);
                }
            );
        } catch (Exception $e) {
            // no need to handle here
        }

        // Check whether the order exists
        if (!$order instanceof Order) {
            throw new RuntimeException('payment.warningOrderDoesNotExist');
        }
        return $order;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderByTxnId($txnId): Order
    {
        $orderId = $this->orderTxnIdRelationRepo->getOrderIdByTxnId($txnId);
        return $this->getOrder($orderId);
    }
}
