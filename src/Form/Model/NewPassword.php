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

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

class NewPassword
{
    /**
     * @var mixed|null
     */
    #[SecurityAssert\UserPassword(
        message: "Wrong value for your current password.",
    )]
    private $currentPassword;

    /**
     * @var mixed|null
     */
    #[Assert\NotBlank]
    #[Assert\IdenticalTo(propertyPath: 'passwordConfirm', message: 'Your password confirmation does not match.')]
    #[Assert\NotIdenticalTo(propertyPath: 'currentPassword', message: 'Your new password must not be identical to your current one.')]
    #[Assert\NotCompromisedPassword]
    #[Assert\Length(max: 4096, min: 8, minMessage: 'Your password must be at least {{ limit }} characters long.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/', message: 'Your password must contain at least one lowercase character.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Lu}\p{Lt}]/', message: 'Your password must contain at least one uppercase character.')]
    #[Assert\Regex(pattern: '/[[:punct:]]/', message: 'Your password must contain at least one special character.')]
    #[Assert\Regex(pattern: '/\p{Nd}/', message: 'Your password must contain at least one numeric character.')]
    private $password;

    /**
     * @var mixed|null
     */
    private $passwordConfirm;

    public function getCurrentPassword(): mixed
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(mixed $currentPassword): NewPassword
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    public function getPassword(): mixed
    {
        return $this->password;
    }

    public function setPassword(mixed $password): NewPassword
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordConfirm(): mixed
    {
        return $this->passwordConfirm;
    }

    public function setPasswordConfirm(mixed $passwordConfirm): NewPassword
    {
        $this->passwordConfirm = $passwordConfirm;

        return $this;
    }
}
