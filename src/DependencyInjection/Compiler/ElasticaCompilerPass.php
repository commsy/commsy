<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ElasticaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $options = [
            'pipeline' => 'attachment',
        ];

        foreach ($container->getDefinitions() as $id => $definition) {
            if (strpos($id, 'fos_elastica.object_persister.') !== false) {
                $definition->setArgument('index_4', $options);
            }
        }
    }
}