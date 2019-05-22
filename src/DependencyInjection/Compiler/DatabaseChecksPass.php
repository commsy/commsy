<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:22
 */

namespace App\DependencyInjection\Compiler;


use App\Database\DatabaseChecks;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DatabaseChecksPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('commsy.database.checks')) {
            return;
        }

        $definition = $container->findDefinition('commsy.database.checks');

        // find all service ids tagged with commsy.database.check
        $taggedServices = $container->findTaggedServiceIds('commsy.database.check');

        foreach ($taggedServices as $id => $tags) {
            // add the check to the DatabaseChecks service
            $definition->addMethodCall('addCheck', [new Reference($id)]);
        }
    }
}