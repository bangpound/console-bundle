<?php

namespace Bangpound\Bundle\ConsoleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddConsoleCommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $commandServices = $container->findTaggedServiceIds('console.command');

        foreach ($commandServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $applicationId = isset($attributes['application']) ? $attributes['application'] : 'bangpound_console.application';
                $definition = $container->getDefinition($applicationId);
                $definition->addMethodCall('add', [new Reference($id)]);
            }
        }
    }
}
