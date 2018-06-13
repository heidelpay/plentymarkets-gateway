<?php
/**
 * Extends the using class with translation capabilities.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/plenty-gateway
 */
namespace Heidelpay\Traits;

use Plenty\Plugin\Translation\Translator as BaseTranslator;

trait Translator
{
    /** @var BaseTranslator */
    private $translator;

    /**
     * @return BaseTranslator
     */
    public function getTranslator(): BaseTranslator
    {
        if (!$this->translator instanceof BaseTranslator) {
            $this->translator = pluginApp(BaseTranslator::class);
        }

        return $this->translator;
    }
}
