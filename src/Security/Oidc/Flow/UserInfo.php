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

namespace App\Security\Oidc\Flow;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UserInfo
{
    public function __construct(
        private string $identifier,
        private string $email,
        private string $firstname,
        private string $lastname,
        private ?string $displayName = null,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }
}
