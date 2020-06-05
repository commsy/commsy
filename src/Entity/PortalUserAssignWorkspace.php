<?php


namespace App\Entity;

/**
 * Class PortalUserChangeStatus
 * @package App\Entity
 */
class PortalUserAssignWorkspace
{

    private $name;

    private $userID;

    private $searchForWorkspace;

    private $descriptionOfParticipation;

    private $workspaceSelection;

    /**
     * @return mixed
     */
    public function getWorkspaceSelection()
    {
        return $this->workspaceSelection;
    }

    /**
     * @param mixed $workspaceSelection
     */
    public function setWorkspaceSelection($workspaceSelection): void
    {
        $this->workspaceSelection = $workspaceSelection;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * @param string $userID
     */
    public function setUserID($userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @return string
     */
    public function getSearchForWorkspace()
    {
        return $this->searchForWorkspace;
    }

    /**
     * @param string $searchForWorkspace
     */
    public function setSearchForWorkspace($searchForWorkspace): void
    {
        $this->searchForWorkspace = $searchForWorkspace;
    }

    /**
     * @return string
     */
    public function getDescriptionOfParticipation()
    {
        return $this->descriptionOfParticipation;
    }

    /**
     * @param string $descriptionOfParticipation
     */
    public function setDescriptionOfParticipation($descriptionOfParticipation): void
    {
        $this->descriptionOfParticipation = $descriptionOfParticipation;
    }
    
    
}