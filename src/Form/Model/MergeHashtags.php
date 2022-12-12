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

namespace App\Form\Model;

use App\Entity\Labels;
use Symfony\Component\Validator\Constraints as Assert;

class MergeHashtags
{
    private ?Labels $first = null;

    #[Assert\NotIdenticalTo(propertyPath: 'first', message: 'Your selection must differ.')]
    private ?Labels $second = null;

    /**
     * @return Labels
     */
    public function getFirst(): ?Labels
    {
        return $this->first;
    }

    public function setFirst(Labels $first): self
    {
        $this->first = $first;

        return $this;
    }

    /**
     * @return Labels
     */
    public function getSecond(): ?Labels
    {
        return $this->second;
    }

    public function setSecond(Labels $second): self
    {
        $this->second = $second;

        return $this;
    }
}
