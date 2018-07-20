<?php

namespace Heidelpay\Constants;

/**
 * Class for Config Key constants
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\constants
 */
class Configuration
{
    //<editor-fold desc="Keys">
    const KEY_AUTH_SENDER_ID = 'main.senderId';
    const KEY_AUTH_PASSWORD = 'main.password';
    const KEY_AUTH_LOGIN = 'main.login';
    const KEY_ENVIRONMENT = 'main.environment';
    const KEY_SECRET = 'main.secret';

    const KEY_IS_ACTIVE = 'isActive';
    const KEY_DISPLAY_NAME = 'displayName';
    const KEY_CHANNEL_ID = 'channelId';

    const KEY_MIN_AMOUNT = 'minAmount';
    const KEY_MAX_AMOUNT = 'maxAmount';

    const KEY_DESCRIPTION_TYPE = 'infoPage.type';
    const KEY_DESCRIPTION_INTERNAL = 'infoPage.intern';
    const KEY_DESCRIPTION_EXTERNAL = 'infoPage.extern';

    const KEY_ICON_PATH = 'iconUrl';

    // credit card and debit card specific
    const KEY_DO_REGISTRATION = 'doRegistration';
    const KEY_IFRAME_CSS_URL = 'iframeCss';
    const KEY_BOOKING_MODE = 'transactionType';
    //</editor-fold>

    //<editor-fold desc="VALUES">
    const VALUE_BOOKING_MODE_DEBIT = 0;
    const VALUE_BOOKING_MODE_RESERVATION = 1;

    const VALUE_ENVIRONMENT_CONNECTOR_TEST = 0;
    const VALUE_ENVIRONMENT_LIVE = 1;
    //</editor-fold>
}
