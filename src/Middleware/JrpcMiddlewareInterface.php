<?php

namespace Pingframework\Jrpc\Middleware;

interface JrpcMiddlewareInterface
{
    public function handle(JrpcRequestContext $ctx): void;
}