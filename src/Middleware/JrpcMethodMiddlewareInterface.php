<?php

namespace Pingframework\Jrpc\Middleware;

interface JrpcMethodMiddlewareInterface
{
    public function handle(JrpcRequestMethodContext $ctx): void;
}