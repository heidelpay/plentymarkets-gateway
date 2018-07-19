<?php
namespace Heidelpay\Migrations;

use Heidelpay\Models\OrderTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Plugin\Log\Loggable;

/**
 * Transactions table migration class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\migrations
 */
class CreateHeidelpayOrderTxnIdRelationTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        $this->getLogger(__METHOD__)->error('Run Migration: ' . self::class);
        $migrate->createTable(OrderTxnIdRelation::class);
    }
}
