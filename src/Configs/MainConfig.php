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
     * Returns the senderId for authentication.
     *
     * @return string
     */
    public function getSenderId(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_SENDER_ID));
    }

    /**
     * Returns the user login for authentication.
     *
     * @return string
     */
    public function getUserLogin(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_LOGIN));
    }

    /**
     * Returns the user password for authentication.
     *
     * @return string
     */
    public function getUserPassword(): string
    {
        return $this->get($this->getConfigKey(ConfigKeys::AUTH_PASSWORD));
    }

    /**
     * Returns true if the shop is configured to work in sandbox mode aka. connector-test mode.
     *
     * @return bool
     */
    public function isInSandboxMode(): bool
    {
        return $this->getMode() === TransactionMode::CONFIG_CONNECTOR_TEST;
    }

    /**
     * Fetches the mode from config.
     *
     * @return int
     */
    private function getMode(): int
    {
        return (int)$this->get($this->getConfigKey(ConfigKeys::ENVIRONMENT));
    }

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->isInSandboxMode() ? TransactionMode::CONNECTOR_TEST : TransactionMode::LIVE;
    }
}
