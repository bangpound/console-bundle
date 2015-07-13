<?php

namespace Bangpound\Bundle\ConsoleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class AutoAddConsoleCommandPass implements CompilerPassInterface
{
    const COMMAND_CLASS = 'Symfony\\Component\\Console\\Command\\Command';

    /**
     * @var BundleInterface[]
     */
    private $bundles;

    /**
     * @param BundleInterface[] $bundles
     */
    public function __construct(array $bundles = array())
    {
        $this->bundles = $bundles;
    }

    /**
     * @see \Symfony\Component\HttpKernel\Bundle::registerCommands
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // Identify classes of already tagged Command services
        // so this pass does not create additional service definitions
        // for them.
        $classes = array_map(function ($id) use ($container) {
            $class = $container->getDefinition($id)->getClass();

            return $container->getParameterBag()->resolveValue($class);
        }, array_keys($container->findTaggedServiceIds('console.command')));

        /** @var BundleInterface $bundle */
        foreach ($this->bundles as $bundle) {
            if (!is_dir($dir = $bundle->getPath().'/Command')) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->name('*Command.php')->in($dir);

            $prefix = $bundle->getNamespace().'\\Command';
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $ns = $prefix;
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\'.strtr($relativePath, '/', '\\');
                }
                $class = $ns.'\\'.$file->getBasename('.php');

                // This command is already in the container.
                if (in_array($class, $classes)) {
                    continue;
                }

                $r = new \ReflectionClass($class);
                if ($r->isSubclassOf(self::COMMAND_CLASS) && !$r->isAbstract() && !$r->getConstructor()->getNumberOfRequiredParameters()) {
                    $definition = new Definition($class);
                    $definition->addTag('console.command');
                    if ($r->isSubclassOf('Symfony\\Component\\DependencyInjection\\ContainerAwareInterface')) {
                        $definition->addMethodCall('setContainer', [new Reference('service_container')]);
                    }
                    $id = 'bangpound_console.command.'.strtolower(str_replace('\\', '_', $class));
                    $container->setDefinition($id, $definition);
                }
            }
        }
    }
}
