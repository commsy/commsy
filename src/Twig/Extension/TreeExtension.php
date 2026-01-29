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

namespace App\Twig\Extension;

use App\Utils\Tree\Tree;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Attribute\AsTwigFunction;

readonly class TreeExtension
{
    private StimulusHelper $stimulus;

    public function __construct(
    ) {
        $this->stimulus = new StimulusHelper(null);
    }

    #[AsTwigFunction(name: 'render_tree', isSafe: ['html'])]
    public function renderTree(Tree $tree): string
    {
        $controllerValues = [];
        $controllerValues['view'] = $tree->createView();

        $treeAttributes = $this->stimulus->createStimulusAttributes();
        $treeAttributes->addController('tree', $controllerValues);

        return sprintf('<div %s></div>', $treeAttributes);
    }
}
