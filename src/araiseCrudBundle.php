<?php

declare(strict_types=1);

namespace araise\CrudBundle;

use araise\CrudBundle\DependencyInjection\Compiler\DefinitionPass;
use araise\CrudBundle\DependencyInjection\Compiler\RemoveUnwantedAutoWiredServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class araiseCrudBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DefinitionPass());
        $container->addCompilerPass(new RemoveUnwantedAutoWiredServicesPass());
    }
}
