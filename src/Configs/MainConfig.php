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

use Heidelpay\Constants\Config;
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
        return $this->get($this->getConfigKey(Config::KEY_AUTH_SENDER_ID));
    }

    /**
     * Returns the user login for authentication.
     *
     * @return string
     */
    public function getUserLogin(): string
    {
        return $this->get($this->getConfigKey(Config::KEY_AUTH_LOGIN));
    }

    /**
     * Returns the user password for authentication.
     *
     * @return string
     */
    public function getUserPassword(): string
    {
        return $this->get($this->getConfigKey(Config::KEY_AUTH_PASSWORD));
    }

    /**
     * Returns true if the shop is configured to work in sandbox mode aka. connector-test mode.
     *
     * @return bool
     */
    public function isInSandboxMode(): bool
    {
        $mode = (int)$this->get($this->getConfigKey(Config::KEY_ENVIRONMENT));
        return $mode === Config::VALUE_ENVIRONMENT_CONNECTOR_TEST;
    }

    /**
     * Returns true if the shop is configured to work in live mode.
     *
     * @return bool
     */
    public function isInLiveMode(): bool
    {
        return !$this->isInSandboxMode();
    }

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return int Config::VALUE_ENVIRONMENT_CONNECTOR_TEST | Config::VALUE_ENVIRONMENT_LIVE
     */
    public function getEnvironment(): int
    {
        return $this->isInSandboxMode() ? TransactionMode::CONNECTOR_TEST : TransactionMode::LIVE;
    }
}
