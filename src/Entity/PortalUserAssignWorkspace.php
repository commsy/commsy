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
    private ?string $name;

    private ?string $userID;

    private ?string $searchForWorkspace;

    private ?string $descriptionOfParticipation;

    private ?string $workspaceSelection;

    public function getWorkspaceSelection(): ?string
    {
        return $this->workspaceSelection;
    }

    public function setWorkspaceSelection(?string $workspaceSelection): void
    {
        $this->workspaceSelection = $workspaceSelection;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getUserID(): ?string
    {
        return $this->userID;
    }

    public function setUserID(?string $userID): void
    {
        $this->userID = $userID;
    }

    public function getSearchForWorkspace(): ?string
    {
        return $this->searchForWorkspace;
    }

    public function setSearchForWorkspace($searchForWorkspace): void
    {
        $this->searchForWorkspace = $searchForWorkspace;
    }

    public function getDescriptionOfParticipation(): ?string
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
