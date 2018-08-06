<?php

namespace Heidelpay\Migrations;

use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\OrderTxnIdRelation;
use Heidelpay\Models\Transaction;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Plugin\Log\Loggable;

/**
 * Transactions table migration class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\migrations
 */
class CreateHeidelpayTables
{
    use Loggable;

    /** @var PaymentHelper */
    private $paymentHelper;

    /**
     * CreateHeidelpayTables constructor.
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper
    )
    {
        $this->paymentHelper = $paymentHelper;
    }

    public function run(Migrate $migrate)
    {
        $this->getLogger(__METHOD__)->error('Run Migration: ' . self::class);
        $migrate->createTable(Transaction::class);
        $migrate->createTable(OrderTxnIdRelation::class);

        $this->paymentHelper->createMopsIfNotExist();
    }
}
