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
namespace Heidelpay\Services;

interface NotificationServiceContract
{
    /**
     * Add debug log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function debug($message, $method = 'no method given', array $logData = []);

    /**
     * Add info notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function info($message, $method = 'no method given', array $logData = []);

    /**
     * Add success notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function success($message, $method = 'no method given', array $logData = []);

    /**
     * Add warning notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function warning($message, $method = 'no context given', array $logData = []);

    /**
     * Add error notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function error($message, $method = 'no context given', array $logData = []);

    /**
     * Add critical notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function critical($message, $method = 'no context given', array $logData = []);
}
