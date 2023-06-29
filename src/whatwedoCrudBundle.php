<?php

declare(strict_types=1);

namespace araise\CrudBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use araise\CrudBundle\DependencyInjection\Compiler\DefinitionPass;
use araise\CrudBundle\DependencyInjection\Compiler\RemoveUnwantedAutoWiredServicesPass;

class whatwedoCrudBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DefinitionPass());
        $container->addCompilerPass(new RemoveUnwantedAutoWiredServicesPass());
    }
}
