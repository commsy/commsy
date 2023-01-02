<?php


namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;

class AccountIndex
{

    private $accountIndexSearchString;

    private $userIndexFilterChoice;

    private $indexViewAction;

    private $accountIndexUsers;

    private array $identifier;

    private array $accounts;

    public function __construct()
    {
    }

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

    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    public function addIdentifier(string $identifier)
    {
        $this->identifier[$identifier] = false;
    }

    public function getAccounts(): array
    {
        return $this->accounts;
    }

    public function addAccount(Account $account)
    {
        $this->accounts[] = $account;
    }
}