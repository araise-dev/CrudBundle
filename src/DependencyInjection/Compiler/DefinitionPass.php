<?php

declare(strict_types=1);

namespace araise\CrudBundle\DependencyInjection\Compiler;

use araise\CrudBundle\ConfigResource\DefinitionResource;
use araise\CrudBundle\Extension\ExtensionInterface;
use araise\CrudBundle\Manager\DefinitionManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DefinitionPass implements CompilerPassInterface
{
    /**
     * this will initialize all Definitions.
     */
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(DefinitionManager::class)) {
            return;
        }

        /*
         * Load Definition Extensions
         */
        foreach (array_keys($container->findTaggedServiceIds('araise_crud.extension')) as $id) {
            $crudExtension = $container->getDefinition($id);

            // all extensions must implement ExtensionInExtensionInterfaceterface
            if (! is_subclass_of($crudExtension->getClass(), ExtensionInterface::class)) {
                throw new \UnexpectedValueException(sprintf('Extensions tagged with araise_crud.extension must implement %s - %s given.', ExtensionInterface::class, $crudExtension->getClass()));
            }

            // remove extensions from container if their requirements (other bundles) are not fulfilled
            if (! call_user_func([$crudExtension->getClass(), 'isEnabled'], [$container->getParameter('kernel.bundles')])) {
                $container->removeDefinition($id);
            }
        }

        /*
         * Load definitions
         */
        foreach ($container->findTaggedServiceIds('araise_crud.definition') as $id => $tags) {
            $crudDefinition = $container->getDefinition($id);

            if ($crudDefinition->isAbstract()) {
                continue;
            }

            $crudDefinition->addMethodCall('setTemplates', [$container->getParameter('araise_crud.config.templates')]);

            // add available extensions to all Definitions
            foreach (array_keys($container->findTaggedServiceIds('araise_crud.extension')) as $idExtension) {
                $crudDefinition->addMethodCall('addExtension', [new Reference($idExtension)]);
            }

            // create a resource so that the cache is loaded new as soon as a definition is updated
            $container->addResource(new DefinitionResource($id));
        }
    }
}
