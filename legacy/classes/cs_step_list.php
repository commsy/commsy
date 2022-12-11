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

class cs_step_list extends cs_list
{
    /** constructor: cs_list
     * the only available constructor, initial values for internal variables.
     *
     * @author CommSy Development Group
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'step_list';
    }

    public function append($step): void
    {
        $step_id = $step->getItemID();
        $this->data[$step_id] = $step;
        ksort($this->data);
    }

    public function set($step): void
    {
        $step_id = $step->getItemID();
        $this->data[$step_id] = $step;
        ksort($this->data);
    }
}
