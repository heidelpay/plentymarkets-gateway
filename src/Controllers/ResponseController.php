<?php

namespace Heidelpay\Controllers;

use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Log\Loggable;

/**
 * heidelpay Response Controller
 *
 * Processes the transaction/payment responses coming from the heidelpay payment system.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\controllers
 */
class ResponseController extends Controller
{
    use Loggable;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var LibraryCallContract $libCall
     */
    private $libCaller;

    /**
     * ResponseController constructor.
     *
     * @param Request             $request
     * @param LibraryCallContract $libCall
     */
    public function __construct(Request $request, LibraryCallContract $libCall)
    {
        $this->request = $request;
        $this->libCaller = $libCall;
    }

    /**
     * Processes the incoming POST response.
     *
     * @return void
     */
    public function processResponse(): void
    {
        $this->getLogger(__METHOD__)->info('Heidelpay::response.received');

        /** @var array $response */
        $response = $this->libCaller->call(
            'Heidelpay::payment_api_responsehandler',
            ['json_response' => json_encode($this->request->all())]
        );
    }
}
