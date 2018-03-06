<?php

namespace Heidelpay\Constants;

/**
 * Class for Config Key constants
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\constants
 */
class ConfigKeys
{
    const AUTH_SENDER_ID = 'main.senderId';
    const AUTH_PASSWORD = 'main.password';
    const AUTH_LOGIN = 'main.login';
    const ENVIRONMENT = 'main.environment';

    const IS_ACTIVE = 'isActive';
    const DISPLAY_NAME = 'displayName';
    const CHANNEL_ID = 'channelId';

    const MIN_AMOUNT = 'minAmount';
    const MAX_AMOUNT = 'maxAmount';

    const DESCRIPTION_TYPE = 'infoPage.type';
    const DESCRIPTION_INTERNAL = 'infoPage.intern';
    const DESCRIPTION_EXTERNAL = 'infoPage.extern';

    const LOGO_USE = 'logo.use';
    const LOGO_URL = 'logo.url';

    // credit card and debit card specific
    const DO_REGISTRATION = 'doRegistration';
    const IFRAME_CSS_URL = 'iframeCss';
}
