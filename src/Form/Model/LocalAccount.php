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

use App\Validator\Constraints\LocalAccount as LocalAccountConstraint;
use Symfony\Component\Validator\Constraints as Assert;

#[LocalAccountConstraint]
class LocalAccount
{
    #[Assert\NotBlank]
    private ?string $username = null;

    public function __construct(private int $contextId)
    {
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): self
    {
        $this->contextId = $contextId;

        return $this;
    }
}
