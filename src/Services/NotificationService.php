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
use Plenty\Plugin\Translation\Translator;

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
     * @var Translator
     */
    private $translator;

    /**
     * NotificationService constructor.
     * @param BaseNotificationService $notifier
     * @param Translator $translator
     */
    public function __construct(
        BaseNotificationService $notifier,
        Translator $translator
    ) {
        $this->notifier = $notifier;
        $this->translator = $translator;
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

    /**
     * @param $level
     * @param $message
     * @param $method
     * @param array $logData
     */
    protected function notify($level, $message, $method, array $logData)
    {
        $message = $this->translator->trans(self::PREFIX . $message);

        switch ($level) {
            case self::LEVEL_DEBUG:
                $this->getLogger($method)->debug($message, $logData);
                break;
            case self::LEVEL_INFO:
                $this->notifier->info($message);
                $this->getLogger($method)->info($message, $logData);
                break;
            case self::LEVEL_SUCCESS:
                $this->notifier->success($message);
                $this->getLogger($method)->debug($message, $logData);
                break;
            case self::LEVEL_WARNING:
                $this->notifier->warn($message);
                $this->getLogger($method)->warning($message, $logData);
                break;
            case self::LEVEL_CRITICAL:
                $this->notifier->error($message);
                $this->getLogger($method)->critical($message, $logData);
                break;
            case self::LEVEL_ERROR: // intended Fall-Through (handle unknown levels as Error)
            default:
                $this->notifier->error($message);
                $this->getLogger($method)->error($message, $logData);
                break;
        }
    }
}
