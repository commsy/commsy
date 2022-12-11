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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class AuthSourceLocal extends AuthSource
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $mailRegex = null;

    protected string $type = 'local';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_YES;
        $this->changeUsername = true;
        $this->deleteAccount = true;
        $this->changeUserdata = true;
        $this->changePassword = true;
    }

    /**
     * @return string
     */
    public function getMailRegex(): ?string
    {
        return $this->mailRegex;
    }

    public function setMailRegex(string $mailRegex): self
    {
        $this->mailRegex = $mailRegex;

        return $this;
    }
}
