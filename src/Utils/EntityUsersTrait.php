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
use Symfony\Component\Serializer\Attribute\Groups;

trait EntityUsersTrait
{
    /**
     * Rendered by {@see \App\Serializer\UserNormalizer}, which applies
     * the person's visibility setting. Not in the `api` group: that would
     * change what the existing room resource answers.
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id', nullable: true)]
    #[Groups(['export'])]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id', nullable: true)]
    #[Groups(['export'])]
    private ?User $modifier = null;

    // No group: deleted entries are not exported.
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $deleter = null;

    public function setCreator(?User $creator = null): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(?User $modifier = null): self
    {
        $this->modifier = $modifier;
        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    public function setDeleter(?User $deleter = null): self
    {
        $this->deleter = $deleter;
        return $this;
    }

    public function getDeleter(): ?User
    {
        return $this->deleter;
    }
}
