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

use JsonException;
use Pingframework\Jrpc\Annotations\JrpcMiddleware;
use Pingframework\Jrpc\Controller\JrpcControllerRegistry;
use Pingframework\Jrpc\Schema\JrpcRequestRootSchema;
use Pingframework\Jrpc\Schema\JrpcResponseRootErrorSchema;
use Pingframework\Jrpc\Schema\JrpcResponseRootSchema;
use Pingframework\Ping\DependencyContainer\DependencyContainerInterface;
use Pingframework\Ping\Utils\Json\JsonDecoderInterface;
use Pingframework\Ping\Utils\ObjectMapper\ObjectMapper;
use Pingframework\Ping\Utils\ObjectMapper\ObjectMapperException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
#[JrpcMiddleware]
class JrpcControllerMiddleware implements JrpcMiddlewareInterface
{
    public function __construct(
        public readonly DependencyContainerInterface $c,
        public readonly JrpcMethodMiddlewareRegistry $middlewareRegistry,
        public readonly JrpcControllerRegistry       $controllerRegistry,
        public readonly ObjectMapper                 $objectMapper,
        public readonly JsonDecoderInterface         $jsonDecoder,
        public readonly LoggerInterface              $logger,
    ) {}

    /**
     * @param JrpcRequestContext $ctx
     * @return void
     * @throws JsonException
     * @throws ObjectMapperException
     */
    public function handle(JrpcRequestContext $ctx): void
    {
        $requestArray = $this->jsonDecoder->unmarshal($ctx->request->getContent());
        $isSingleRequest = array_key_exists('jsonrpc', $requestArray);
        if ($isSingleRequest) {
            $requestArray = [$requestArray];
        }

        $requests = $this->objectMapper->mapListFromArray($requestArray, JrpcRequestRootSchema::class);
        $responses = [];

        foreach ($requests as $requestRootSchema) {
            $responseRootSchema = new JrpcResponseRootSchema();
            $responses[] = $responseRootSchema;
            $responseRootSchema->id = $requestRootSchema->id;

            $ctx->runtime[JrpcRequestRootSchema::class] = $requestRootSchema;
            $ctx->runtime[JrpcResponseRootSchema::class] = $responseRootSchema;

            try {
                $cd = $this->controllerRegistry->get($requestRootSchema->method);
                $jrpcCtx = new JrpcRequestMethodContext(
                    $ctx->request,
                    $ctx->response,
                    $requestRootSchema,
                    $responseRootSchema,
                    $cd,
                    $ctx->runtime
                );
                $jrpcCtx->runtime[JrpcRequestMethodContext::class] = $jrpcCtx;

                foreach ($this->middlewareRegistry->middlewares as $middleware) {
                    $middleware->handle($jrpcCtx);
                }

                $result = $this->c->call($this->c->get($cd->serviceClass), $cd->serviceMethod, $jrpcCtx->runtime);

                if (is_object($result)) {
                    $responseRootSchema->result = $this->objectMapper->unmapToArray($result);
                    continue;
                }

                if ($this->isUnmappableList($result)) {
                    $responseRootSchema->result = $this->objectMapper->unmapListToArray($result);
                    continue;
                }

                $responseRootSchema->result = $result;
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        "JRPC error: %s. in file: %s(%s). Stack trace: \n%s",
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    )
                );
                $responseRootSchema->error = JrpcResponseRootErrorSchema::fromException($e);
            }
        }

        if ($isSingleRequest) {
            $ctx->response->write($this->objectMapper->unmapToJson($responses[0] ?? []));
        } else {
            $ctx->response->write($this->objectMapper->unmapListToJson($responses));
        }
    }

    private function isUnmappableList(mixed $result): bool
    {
        if (!is_array($result)) {
            return false;
        }

        if (count($result) === 0) {
            return false;
        }

        if (is_object(reset($result))) {
            return true;
        }

        return false;
    }
}