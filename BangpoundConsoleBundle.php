<?php

namespace Bangpound\Bundle\ConsoleBundle;

use Bangpound\Bundle\ConsoleBundle\DependencyInjection\Compiler\AddConsoleCommandPass;
use Bangpound\Bundle\ConsoleBundle\DependencyInjection\Compiler\AutoAddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class BangpoundConsoleBundle extends Bundle
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function build(ContainerBuilder $container)
    {
        $config = $container->getCompilerPassConfig();
        $passes = $config->getBeforeOptimizationPasses();

        $pass = new AutoAddConsoleCommandPass($this->kernel->getBundles());
        array_unshift($passes, $pass);

        $config->setBeforeOptimizationPasses($passes);

        $container->addCompilerPass(new AddConsoleCommandPass());
    }
}
