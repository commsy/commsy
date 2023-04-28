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

use Symfony\Component\Form\AbstractType;

class AccountIndexUser extends AbstractType
{
    /**
     * @var mixed|null
     */
    private $itemId;

    /**
     * @var mixed|null
     */
    private $name;

    /**
     * @var mixed|null
     */
    private $mail;

    /**
     * @var mixed|null
     */
    private $userId;

    private ?bool $checked = null;

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    public function setItemId(mixed $itemId): void
    {
        $this->itemId = $itemId;
    }

    /**
     * @return mixed
     */
    public function getBlockPrefix(): string
    {
        return $this->name;
    }

    public function setName(mixed $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getMail()
    {
        return $this->mail;
    }

    public function setMail(mixed $mail): void
    {
        $this->mail = $mail;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(mixed $userId): void
    {
        $this->userId = $userId;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): void
    {
        $this->checked = $checked;
    }
}
