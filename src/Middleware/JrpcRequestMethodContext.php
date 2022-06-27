<?php

/**
 * Ping - JRPC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * Json RPC://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@phpsuit.net so we can send you a copy immediately.
 *
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Pingframework\Jrpc\Middleware;

use Pingframework\Jrpc\Controller\JrpcControllerDefinition;
use Pingframework\Jrpc\Schema\JrpcRequestRootSchema;
use Pingframework\Jrpc\Schema\JrpcResponseRootSchema;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JrpcRequestMethodContext extends JrpcRequestContext
{
    public function __construct(
        Request                                  $request,
        Response                                 $response,
        public readonly JrpcRequestRootSchema    $requestRootSchema,
        public readonly JrpcResponseRootSchema   $responseRootSchema,
        public readonly JrpcControllerDefinition $controllerDefinition,
        array                                    $runtime = []
    ) {
        parent::__construct($request, $response, $runtime);
    }
}