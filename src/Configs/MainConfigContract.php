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

interface MainConfigContract
{
    /**
     * Returns the senderId for authentication.
     *
     * @return string
     */
    public function getSenderId(): string;

    /**
     * Returns the user login for authentication.
     *
     * @return string
     */
    public function getUserLogin(): string;

    /**
     * Returns the user password for authentication.
     *
     * @return string
     */
    public function getUserPassword(): string;

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return string Config::VALUE_ENVIRONMENT_CONNECTOR_TEST | Config::VALUE_ENVIRONMENT_LIVE
     */
    public function getEnvironment(): string;

    /**
     * Returns the secret key.
     *
     * @return string|null
     */
    public function getSecretKey();

    /**
     * Returns true if the shop is configured to work in sandbox mode aka. connector-test mode.
     *
     * @return bool
     */
    public function isInSandboxMode(): bool;

    /**
     * Returns true if the shop is configured to work in live mode.
     *
     * @return bool
     */
    public function isInLiveMode(): bool;
}
