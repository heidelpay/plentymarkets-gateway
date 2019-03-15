<?php

namespace Heidelpay\Services;

use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;

class OrderService implements OrderServiceContract
{
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepo;
    /**
     * @var OrderTxnIdRelationRepositoryContract
     */
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
    public function getLanguage(Order $order): string
    {
        /** @var OrderProperty $property */
        foreach ($order->properties as $property) {
            if ($property->typeId === OrderPropertyType::DOCUMENT_LANGUAGE) {
                return $property->value;
            }
        }

        return 'DE';
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
        } catch (\Exception $e) {
            // no need to handle here
        }

        // Check whether the order exists
        if (!$order instanceof Order) {
            throw new \RuntimeException('payment.warningOrderDoesNotExist');
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
