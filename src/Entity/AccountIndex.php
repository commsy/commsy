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

class AccountIndex
{
    /**
     * @var mixed|null
     */
    private $accountIndexSearchString;

    /**
     * @var mixed|null
     */
    private $userIndexFilterChoice;

    /**
     * @var mixed|null
     */
    private $indexViewAction;

    /**
     * @var mixed|null
     */
    private $accountIndexUsers;

    /**
     * @var mixed|null
     */
    private $ids;

    /**
     * @return mixed
     */
    public function getAccountIndexSearchString()
    {
        return $this->accountIndexSearchString;
    }

    public function setAccountIndexSearchString(mixed $accountIndexSearchString): void
    {
        $this->accountIndexSearchString = $accountIndexSearchString;
    }

    /**
     * @return mixed
     */
    public function getUserIndexFilterChoice()
    {
        return $this->userIndexFilterChoice;
    }

    public function setUserIndexFilterChoice(mixed $userIndexFilterChoice): void
    {
        $this->userIndexFilterChoice = $userIndexFilterChoice;
    }

    /**
     * @return mixed
     */
    public function getIndexViewAction()
    {
        return $this->indexViewAction;
    }

    public function setIndexViewAction(mixed $indexViewAction): void
    {
        $this->indexViewAction = $indexViewAction;
    }

    /**
     * @return mixed
     */
    public function getAccountIndexUsers()
    {
        return $this->accountIndexUsers;
    }

    public function setAccountIndexUsers(mixed $accountIndexUsers): void
    {
        $this->accountIndexUsers = $accountIndexUsers;
    }

    /**
     * @return mixed
     */
    public function getIds()
    {
        return $this->ids;
    }

    public function setIds(mixed $ids): void
    {
        $this->ids = $ids;
    }
}
