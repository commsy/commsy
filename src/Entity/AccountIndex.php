<?php


namespace App\Entity;


class AccountIndex
{

    private $accountIndexSearchString;

    private $userIndexFilterChoice;

    private $indexViewAction;

    private $accountIndexUsers;

    private $ids;

    /**
     * @return mixed
     */
    public function getAccountIndexSearchString()
    {
        return $this->accountIndexSearchString;
    }

    /**
     * @param mixed $accountIndexSearchString
     */
    public function setAccountIndexSearchString($accountIndexSearchString): void
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

    /**
     * @param mixed $userIndexFilterChoice
     */
    public function setUserIndexFilterChoice($userIndexFilterChoice): void
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

    /**
     * @param mixed $indexViewAction
     */
    public function setIndexViewAction($indexViewAction): void
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

    /**
     * @param mixed $accountIndexUsers
     */
    public function setAccountIndexUsers($accountIndexUsers): void
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

    /**
     * @param mixed $ids
     */
    public function setIds($ids): void
    {
        $this->ids = $ids;
    }

}