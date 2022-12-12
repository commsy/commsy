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

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Password
{
    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword]
    #[Assert\Length(max: 4096, min: 8, allowEmptyString: false, minMessage: 'Your password must be at least {{ limit }} characters long.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/', message: 'Your password must contain at least one lowercase character.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Lu}\p{Lt}]/', message: 'Your password must contain at least one uppercase character.')]
    #[Assert\Regex(pattern: '/[[:punct:]]/', message: 'Your password must contain at least one special character.')]
    #[Assert\Regex(pattern: '/\p{Nd}/', message: 'Your password must contain at least one numeric character.')]
    private ?string $password = null;

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
