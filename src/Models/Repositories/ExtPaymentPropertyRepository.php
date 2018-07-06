<?php
/**
 * ExtPaymentPropertyRepository class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\models\repositories
 */
namespace Heidelpay\Models\Repositories;

use Plenty\Modules\Payment\Contracts\ExtPaymentPropertyRepositoryContract;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class ExtPaymentPropertyRepository implements ExtPaymentPropertyRepositoryContract
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * PaymentTxnIdRelationRepository constructor.
     *
     * @param DataBase $dataBase
     */
    public function __construct(DataBase $dataBase)
    {
        $this->database = $dataBase;
    }

    /**
     * {@inheritDoc}
     */
    public function allByTypeIdAndValue(
        int $typeId,
        string $value
    ): array {
        return $this->database->query(PaymentProperty::class)
            ->where('typeId', '=', $typeId)
            ->where('value', '=', $value)
            ->get();
    }
}
