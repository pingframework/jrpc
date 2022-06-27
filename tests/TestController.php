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

namespace Pingframework\Jrpc\Tests;

use Pingframework\Jrpc\Annotations\JrpcController;
use Pingframework\Jrpc\Annotations\JrpcRequestField;
use Pingframework\Jrpc\Annotations\JrpcRequestSchema;
use Pingframework\Ping\Annotations\NewInstance;
use Pingframework\Ping\Annotations\Service;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
#[Service]
class TestController
{
    public readonly string $foo;
    public readonly int    $baz;

    #[JrpcController('test.test')]
    public function test(
        string             $foo,
        int                $baz,
        #[JrpcRequestField]
        int $userId,
        #[JrpcRequestSchema]
        TestRequestSchema $requestSchema,
        #[NewInstance]
        TestResponseSchema $responseSchema,
    ): TestResponseSchema {
        $this->foo = $foo;
        $this->baz = $baz;

        $responseSchema->userId = $requestSchema->userId;
        $responseSchema->nickname = $requestSchema->nickname;
        $responseSchema->arr = $requestSchema->arr;
        return $responseSchema;
    }
}