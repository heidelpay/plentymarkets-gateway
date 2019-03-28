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
 * @package  heidelpay\plentymarkets-gateway\services
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
     * @param Translator $translator
     */
    public function __construct(
        Translator $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, $method = 'no method given', array $logData = [])
    {
        $this->notify(self::LEVEL_DEBUG, $message, $method, $logData);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, $method = 'no method given', array $logData = [], $justLog = false)
    {
        $this->notify(self::LEVEL_INFO, $message, $method, $logData, $justLog);
    }

    /**
     * {@inheritDoc}
     */
    public function success($message, $method = 'no method given', array $logData = [], $justLog = false)
    {
        $this->notify(self::LEVEL_SUCCESS, $message, $method, $logData, $justLog);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, $method = 'no context given', array $logData = [], $justLog = false)
    {
        $this->notify(self::LEVEL_WARNING, $message, $method, $logData, $justLog);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, $method = 'no context given', array $logData = [], $justLog = false)
    {
        $this->notify(self::LEVEL_ERROR, $message, $method, $logData, $justLog);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, $method = 'no context given', array $logData = [])
    {
        $this->notify(self::LEVEL_CRITICAL, $message, $method, $logData);
    }

    /**
     * @param $level
     * @param $message
     * @param $method
     * @param array $logData
     * @param bool $justLog
     */
    protected function notify($level, $message, $method, array $logData, $justLog = false)
    {
        $message = self::PREFIX . $message;
        $translation = $this->getTranslation($message);

        switch ($level) {
            case self::LEVEL_DEBUG:
                $this->getLogger($method)->debug($message, $logData);
                break;
            case self::LEVEL_INFO:
                if (!$justLog) {
                    $this->getNotifier()->info($translation);
                }
                $this->getLogger($method)->info($message, $logData);
                break;
            case self::LEVEL_SUCCESS:
                if (!$justLog) {
                    $this->getNotifier()->success($translation);
                }
                $this->getLogger($method)->debug($message, $logData);
                break;
            case self::LEVEL_WARNING:
                if (!$justLog) {
                    $this->getNotifier()->warn($translation);
                }
                $this->getLogger($method)->warning($message, $logData);
                break;
            case self::LEVEL_ERROR:
                if (!$justLog) {
                    $this->getNotifier()->error($translation);
                }
                $this->getLogger($method)->error($message, $logData);
                break;
            case self::LEVEL_CRITICAL: // intended Fall-Through (handle unknown levels as critical)
            default:
                // The client gets a general error message.
                $this->getNotifier()->error($this->getTranslation('general.errorGeneralErrorTryAgainLater'));
                $this->getLogger($method)->critical($message, $logData);
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslation($message, $parameters = [], $locale = null)
    {
        return $this->translator->trans($message, $parameters, $locale);
    }

    /**
     * @return BaseNotificationService
     */
    public function getNotifier(): BaseNotificationService
    {
        if (!$this->notifier instanceof BaseNotificationService) {
            $this->notifier = pluginApp(BaseNotificationService::class);
        }

        return $this->notifier;
    }
}
