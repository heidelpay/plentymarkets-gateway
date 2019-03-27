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

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;

class PaymentInfoService implements PaymentInfoServiceContract
{
    /** @var NotificationServiceContract */
    private $notificationService;

    /** @var PaymentHelper */
    private $paymentHelper;

    /** @var CommentRepositoryContract */
    private $commentRepo;

    /** @var OrderServiceContract */
    private $orderService;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * PaymentInformationService constructor.
     * @param NotificationServiceContract $notificationService
     * @param PaymentHelper $paymentHelper
     * @param CommentRepositoryContract $commentRepo
     * @param OrderServiceContract $orderService
     * @param OrderRepositoryContract $orderRepository
     */
    public function __construct(
        NotificationServiceContract $notificationService,
        PaymentHelper $paymentHelper,
        CommentRepositoryContract $commentRepo,
        OrderServiceContract $orderService,
        OrderRepositoryContract $orderRepository
    ) {
        $this->notificationService = $notificationService;
        $this->paymentHelper = $paymentHelper;
        $this->commentRepo = $commentRepo;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentInformationString(Order $order, $language, $glue = PHP_EOL): string
    {
        $paymentDetails = $this->paymentHelper->getPaymentDetailsForOrder($order);

        $adviceParts                = [
            $this->notificationService->getTranslation('Heidelpay::template.pleaseTransferTheTotalTo', [], $language),
            $this->notificationService->getTranslation('Heidelpay::template.accountIban', [], $language) . ': ' .
            $paymentDetails['accountIBAN'],
            $this->notificationService->getTranslation('Heidelpay::template.accountBic', [], $language) . ': ' .
            $paymentDetails['accountBIC'],
            $this->notificationService->getTranslation('Heidelpay::template.accountHolder', [], $language) . ': ' .
            $paymentDetails['accountHolder'],
            $this->notificationService->getTranslation('Heidelpay::template.accountUsage', [], $language) . ': ' .
            $paymentDetails['accountUsage']
        ];
        return implode($glue, $adviceParts);
    }

    /**
     * {@inheritDoc}
     */
    public function addPaymentInfoToOrder(int $orderId)
    {
        $order = $this->orderRepository->findOrderById($orderId);
        $instance = $this->paymentHelper->getPaymentMethodInstanceByMopId($order->methodOfPaymentId);
        if (!$instance instanceof AbstractMethod || !$instance->renderInvoiceData()) {
            return;
        }

        $language = $this->orderService->getLanguage($order);
        $commentText = $this->getPaymentInformationString($order, $language, '</br>');

        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);
        $authHelper->processUnguarded(
            function () use ($orderId, $commentText) {
                $this->commentRepo->createComment(
                    [
                        'referenceType'       => Comment::REFERENCE_TYPE_ORDER,
                        'referenceValue'      => $orderId,
                        'text'                => $commentText,
                        'isVisibleForContact' => true
                    ]
                );
            });
    }
}
