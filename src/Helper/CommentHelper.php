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

use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Comment\Models\Comment;

class CommentHelper
{
    /** @var CommentRepositoryContract */
    private $commentRepo;

    /**
     * OrderCommentHelper constructor.
     *
     * @param CommentRepositoryContract $commentRepo
     */
    public function __construct(CommentRepositoryContract $commentRepo)
    {
        $this->commentRepo = $commentRepo;
    }

    /**
     * Adds a note to the order, which can be seen in the shop backend.
     *
     * @param int $orderId
     * @param string $commentText
     */
    public function createOrderComment(int $orderId, string $commentText)
    {
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
            }
        );
    }
}
