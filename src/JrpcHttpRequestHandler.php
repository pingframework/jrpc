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

namespace Pingframework\Jrpc;

use Pingframework\Boot\Annotations\HttpRequestHandler;
use Pingframework\Boot\Http\Server\HttpRequestHandlerInterface;
use Pingframework\Jrpc\Middleware\JrpcMiddlewareRegistry;
use Pingframework\Jrpc\Middleware\JrpcRequestContext;
use Pingframework\Jrpc\Schema\JrpcResponseRootErrorSchema;
use Pingframework\Ping\Annotations\Inject;
use Pingframework\Ping\Utils\ObjectMapper\ObjectMapper;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
#[HttpRequestHandler]
class JrpcHttpRequestHandler implements HttpRequestHandlerInterface
{
    public const CONFIG_JRPC_METHOD         = 'jrpc.method';
    public const CONFIG_JRPC_URI            = 'jrpc.uri';
    public const CONFIG_JRPC_DISPLAY_ERRORS = 'jrpc.display_errors';

    public function __construct(
        public readonly JrpcMiddlewareRegistry $middlewareRegistry,
        public readonly ObjectMapper           $objectMapper,
        public readonly LoggerInterface        $logger,
        #[Inject(self::CONFIG_JRPC_METHOD)]
        public readonly string                 $method = "POST",
        #[Inject(self::CONFIG_JRPC_URI)]
        public readonly string                 $uri = "/jrpc",
        #[Inject(self::CONFIG_JRPC_DISPLAY_ERRORS)]
        public readonly bool                   $displayErrorsFlag = false,
    ) {}

    public function handle(Request $request, Response $response): void
    {
        try {
            assert($request->getMethod() === $this->method, 'Invalid request method');
            assert($request->server['request_uri'] === $this->uri, 'Invalid request uri');
            assert(isset($request->header['accept']), 'Invalid accept header');
            assert($request->header['accept'] === 'application/json', 'Invalid accept header');
            assert(isset($request->header['content-type']), 'Invalid content type');
            assert($request->header['content-type'] === 'application/json', 'Invalid content type');


            $ctx = new JrpcRequestContext(
                $request,
                $response,
            );

            $ctx->runtime = [
                Request::class            => $request,
                Response::class           => $response,
                JrpcRequestContext::class => $ctx
            ];

            foreach ($this->middlewareRegistry->middlewares as $middleware) {
                $middleware->handle($ctx);
            }
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    "Internal server error. %s(%s): %s. in %s on line %s\n%s",
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                )
            );
            $response->status(200);
            $response->end($this->objectMapper->unmapToJson(JrpcResponseRootErrorSchema::fromException($e)));
            return;
        }
    }
}