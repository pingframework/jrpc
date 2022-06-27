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

namespace Pingframework\Jrpc\Controller;

use Pingframework\Ping\Annotations\Service;
use RuntimeException;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
#[Service]
class JrpcControllerRegistry
{
    /**
     * @var array<string, JrpcControllerDefinition>
     */
    private array $map = [];

    public function put(
        string $jrpcMethod,
        string $serviceClass,
        string $serviceMethod,
    ): void {
        $this->map[$jrpcMethod] = new JrpcControllerDefinition($jrpcMethod, $serviceClass, $serviceMethod);
    }

    public function get(string $jrpcMethod): JrpcControllerDefinition
    {
        if (!isset($this->map[$jrpcMethod])) {
            throw new RuntimeException("Json RPC method $jrpcMethod not found");
        }

        return $this->map[$jrpcMethod];
    }
}