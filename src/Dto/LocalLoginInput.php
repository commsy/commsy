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

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class LocalLoginInput
{
    #[Assert\NotBlank]
    #[Groups(['api_check_local_login'])]
    private int $contextId;

    #[Assert\NotBlank]
    #[Groups(['api_check_local_login'])]
    private string $username;

    #[Assert\NotBlank]
    #[Groups(['api_check_local_login'])]
    private string $password;

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): LocalLoginInput
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): LocalLoginInput
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): LocalLoginInput
    {
        $this->password = $password;

        return $this;
    }
}
