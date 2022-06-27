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

namespace Pingframework\Jrpc\Annotations;

use Attribute;
use Pingframework\Jrpc\Middleware\JrpcRequestMethodContext;
use Pingframework\Ping\Annotations\Injector;
use Pingframework\Ping\DependencyContainer\Definition\VariadicDefinitionMap;
use Pingframework\Ping\DependencyContainer\DependencyContainerInterface;
use Pingframework\Ping\Utils\ObjectMapper\DefaultObjectMapper;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;

/**
 * @author    Oleg Bronzov <oleg.bronzov@gmail.com>
 * @copyright 2022
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class JrpcRequestSchemaList implements Injector
{
    public function __construct(
        public readonly string $type
    ) {}

    public function inject(
        DependencyContainerInterface           $c,
        VariadicDefinitionMap                  $vdm,
        ReflectionClass                        $rc,
        ?ReflectionMethod                      $rm,
        ReflectionParameter|ReflectionProperty $rp,
        array                                  $runtime
    ): mixed {
        if ($rp->getType()->getName() !== 'array') {
            throw new RuntimeException(
                sprintf(
                    'Unsupported argument %s type of request schema list in %s::%s',
                    $rp->getName(),
                    $rc->getName(),
                    $rm->getName()
                )
            );
        }

        $om = new DefaultObjectMapper();
        return $om->mapListFromArray(
            $runtime[JrpcRequestMethodContext::class]->requestRootSchema->params,
            $this->type
        );
    }

}