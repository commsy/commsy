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

namespace App\Action\Delete;

use cs_item;
use cs_step_item;
use Symfony\Component\Routing\RouterInterface;

class DeleteStep implements DeleteInterface
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function delete(cs_item $item): void
    {
        $item->delete();
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        /** @var cs_step_item $step */
        $step = $item;

        return $this->router->generate('app_todo_detail', [
            'roomId' => $step->getContextID(),
            'itemId' => $step->getTodoID(),
        ]);
    }
}
