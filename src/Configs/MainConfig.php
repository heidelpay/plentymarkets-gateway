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
namespace Heidelpay\Configs;

use Heidelpay\Constants\ConfigKeys;
use Heidelpay\Constants\TransactionMode;

class MainConfig extends BaseConfig implements MainConfigContract
{
    /**
     * Returns the senderId for authentification.
     *
     * @return string
     */
    public function getSenderId(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_SENDER_ID));
    }

    /**
     * Returns the user login for authentification.
     *
     * @return string
     */
    public function getUserLogin(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_LOGIN));
    }

    /**
     * Returns the user password for authentification.
     *
     * @return string
     */
    public function getUserPassword(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_PASSWORD));
    }

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return string
     *
     * todo: keine Magic numbers
     */
    public function getEnvironment(): string
    {
        $transactionMode = (int) $this->get($this->getConfigKey(ConfigKeys::ENVIRONMENT));

        if ($transactionMode === 0) {
            return TransactionMode::CONNECTOR_TEST;
        }

        return TransactionMode::LIVE;
    }
}
