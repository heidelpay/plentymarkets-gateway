<?php
/**
 * PaymentTxnIdRelation table
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\migrations
 */
namespace Heidelpay\Migrations;

use Heidelpay\Models\PaymentTxnIdRelation;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreatePaymentTxnIdRelationTable
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable(PaymentTxnIdRelation::class);
    }
}
