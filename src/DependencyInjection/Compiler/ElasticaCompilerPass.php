<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ElasticaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $options = [
            'pipeline' => 'attachment',
        ];

        foreach ($container->getDefinitions() as $id => $definition) {
            if (str_contains($id, 'fos_elastica.object_persister.')) {
                $definition->setArgument('index_4', $options);
            }
        }
    }
}
