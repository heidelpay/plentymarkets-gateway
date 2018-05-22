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

namespace Heidelpay\Helper;

interface MainConfigContract
{
    /**
     * Returns the senderId for authentification.
     *
     * @return string
     */
    public function getSenderId(): string;

    /**
     * Returns the user login for authentification.
     *
     * @return string
     */
    public function getUserLogin(): string;

    /**
     * Returns the user password for authentification.
     *
     * @return string
     */
    public function getUserPassword(): string;

    /**
     * Returns the value for the transaction mode (which is the environment).
     *
     * @return string
     *
     * todo: keine Magic numbers
     */
    public function getEnvironment(): string;
}
