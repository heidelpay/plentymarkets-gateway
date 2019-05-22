<?php
/**
 * Interface for the notification service.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\services
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
     * @param bool $justLog
     */
    public function info($message, $method = 'no method given', array $logData = [], $justLog = false);

    /**
     * Add success notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     * @param bool $justLog
     */
    public function success($message, $method = 'no method given', array $logData = [], $justLog = false);

    /**
     * Add warning notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     * @param bool $justLog
     */
    public function warning($message, $method = 'no context given', array $logData = [], $justLog = false);

    /**
     * Add error notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     * @param bool $justLog
     */
    public function error($message, $method = 'no context given', array $logData = [], $justLog = false);

    /**
     * Add critical notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function critical($message, $method = 'no context given', array $logData = []);

    /**
     * Translates the given message using the given locale.
     *
     * @param $message
     * @param array $parameters
     * @param string $locale
     * @return mixed
     */
    public function translate($message, $parameters = [], $locale = null);
}
