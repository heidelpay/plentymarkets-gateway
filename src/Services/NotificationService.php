<?php
/**
 * This service allows to add translated client notifications and log messages.
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

use Heidelpay\Constants\Plugin;
use IO\Services\NotificationService as BaseNotificationService;
use Plenty\Plugin\Log\Loggable;

class NotificationService implements NotificationServiceContract
{
    use Loggable;

    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    const PREFIX = Plugin::NAME . '::';


    /**
     * @var BaseNotificationService
     */
    private $notifier;


    /**
     * NotificationService constructor.
     * @param BaseNotificationService $notifier
     */
    public function __construct(
        BaseNotificationService $notifier
    ) {
        $this->notifier = $notifier;
    }

    /**
     * Add success notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function success($message, $method = 'no method given', array $logData = [])
    {
        $this->notify(self::LEVEL_SUCCESS, $message, $method, $logData);
    }

    /**
     * Add error notification and log
     *
     * @param string $message
     * @param string $method
     * @param array $logData
     */
    public function error($message, $method = 'no context given', array $logData = [])
    {
        $this->notify(self::LEVEL_ERROR, $message, $method, $logData);
    }

//    /**
//     * Adds a log message of the given level.
//     *
//     * @param $context
//     * @param $message
//     * @param $level
//     * @param array $logData
//     */
//    public function justLog($context, $message, $level, array $logData = [])
//    {
//
//
////        switch ($level) {
////            case self::LEVEL_DEBUG:
////
////        }
//    }

    /**
     * @param $level
     * @param $message
     * @param $method
     * @param array $logData
     */
    protected function notify($level, $message, $method, array $logData)
    {
        $message = self::PREFIX . $message;

        // add notification
        $this->notifier->$level($message);

        $this->getLogger($method)->$level($message, $logData);

//        // add log
//        $this->justLog($method, $message, $level, $logData);
    }
}
