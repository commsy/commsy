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

namespace App\Mail;

class Recipient
{
    private ?string $email = null;

    private bool $isEmailVisible = true;

    private ?string $firstname = null;

    private ?string $lastname = null;

    private ?string $fullname = null;

    private ?string $language = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isEmailVisible(): bool
    {
        return $this->isEmailVisible;
    }

    public function setIsEmailVisible(bool $isEmailVisible): self
    {
        $this->isEmailVisible = $isEmailVisible;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    // NOTE: We allow to explicitly set $fullname since, in some cases, individual name
    //       parts aren't available. However, if $fullname has not been set explicitly,
    //       we assemble the full name from $firstname and $lastname.
    public function setFullName(?string $fullname): Recipient
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getFullName(): string
    {
        $fullname = $this->fullname;

        if (!empty($fullname)) {
            return $fullname;
        }

        $fullnameParts = [];
        if (!empty($this->getFirstname())) {
            $fullnameParts[] = $this->getFirstname();
        }
        if (!empty($this->getLastname())) {
            $fullnameParts[] = $this->getLastname();
        }

        return !empty($fullnameParts) ? implode(' ', $fullnameParts) : '';
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }
}
