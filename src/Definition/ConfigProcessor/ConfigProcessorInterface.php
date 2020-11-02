<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Closure;

interface ConfigProcessorInterface
{
    public function process(array &$config): void;
}
