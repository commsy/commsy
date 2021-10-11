<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

class cs_step_list extends cs_list
{
    /** constructor: cs_list
     * the only available constructor, initial values for internal variables
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