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
namespace Heidelpay\Models;

use Heidelpay\Constants\Plugin;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class BaseModel extends Model
{
    const TABLE_NAME = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        if (empty(static::TABLE_NAME)) {
            throw new \RuntimeException('Tablename is empty');
        }

        return Plugin::NAME . '::' . static::TABLE_NAME;
    }
}
