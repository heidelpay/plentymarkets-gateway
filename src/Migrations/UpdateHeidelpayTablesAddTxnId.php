<?php

namespace Heidelpay\Migrations;

use Heidelpay\Models\Transaction;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

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
class UpdateHeidelpayTablesAddTxnId
{
    public function run(Migrate $migrate)
    {
        $migrate->updateTable(Transaction::class);
    }
}
