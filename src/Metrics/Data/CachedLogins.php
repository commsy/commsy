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

namespace App\Metrics\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class CachedLogins
{
    private Collection $logins;

    public function __construct()
    {
        $this->logins = new ArrayCollection();
    }

    public function getLogins(): Collection
    {
        return $this->logins;
    }

    public function setLogins(Collection $logins): CachedLogins
    {
        $this->logins = $logins;
        return $this;
    }
}
