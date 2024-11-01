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

namespace App\Utils;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait EntityUsersTrait
{
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $modifier = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\Column(name: 'deleter_id', nullable: true)]
    private ?int $deleterId = null;

    public function setCreator(User $creator = null): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(User $modifier = null): self
    {
        $this->modifier = $modifier;
        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    public function setDeleterId(?int $deleterId = null): self
    {
        $this->deleterId = $deleterId;
        return $this;
    }

    public function getDeleterId(): ?int
    {
        return $this->deleterId;
    }
}
