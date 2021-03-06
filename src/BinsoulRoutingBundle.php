<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing;

use BinSoul\Symfony\Bundle\Routing\DependencyInjection\Compiler\OverrideRouterPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BinsoulRoutingBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideRouterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -16);
    }
}
