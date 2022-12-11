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

/**
 * Class PortalUserChangeStatus.
 */
class PortalUserAssignWorkspace
{
    private $name;

    private $userID;

    private $searchForWorkspace;

    private $descriptionOfParticipation;

    /**
     * @var mixed|null
     */
    private $workspaceSelection;

    /**
     * @return mixed
     */
    public function getWorkspaceSelection()
    {
        return $this->workspaceSelection;
    }

    public function setWorkspaceSelection(mixed $workspaceSelection): void
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
