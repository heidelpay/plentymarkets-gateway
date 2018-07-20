<?php

namespace Heidelpay\Constants;

/**
 * Constant class for transaction types
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
class TransactionType
{
    const AUTHORIZE = 'authorize';
    const CAPTURE = 'capture';
    const DEBIT = 'debit';
    const RECEIPT = 'receipt';
    const FINALIZE = 'finalize';
    const CREDIT = 'credit';
    const REVERSAL = 'reversal';
    const REFUND = 'refund';
    const REBILL = 'rebill';
    const CHARGEBACK = 'chargeback';
    const INITIALIZE = 'initialize';
    const REGISTRATION = 'registration';
    const REREGISTRATION = 'reregistration';
    const DEREGISTRATION = 'deregistration';

    const HP_AUTHORIZE = 'PA';
    const HP_CAPTURE = 'CP';
    const HP_DEBIT = 'DB';
    const HP_RECEIPT = 'RC';
    const HP_FINALIZE = 'FI';
    const HP_CREDIT = 'CD';
    const HP_REVERSAL = 'RV';
    const HP_REFUND = 'RF';
    const HP_REBILL = 'RB';
    const HP_CHARGEBACK = 'CB';
    const HP_INITIALIZE = 'IN';
    const HP_REGISTRATION = 'RG';
    const HP_REREGISTRATION = 'RR';
    const HP_DEREGISTRATION = 'DR';
}
