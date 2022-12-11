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

namespace App\Model\API;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class Room
{
    /**
     * @OA\Property(type="string", maxLength=255)
     */
    #[Groups(['api_write'])]
    #[Assert\NotBlank(groups: ['api_write'])]
    private ?string $title = null;

    /**
     * @OA\Property(description="Either project or community")
     */
    #[Groups(['api_write'])]
    #[Assert\NotBlank(groups: ['api_write'])]
    #[Assert\Regex('/^(project|community)$/', groups: ['api_write'])]
    private string $type = 'project';

    #[Groups(['api_write'])]
    private ?string $description = null;

    /**
     * @OA\Property(description="The username of the room creator")
     */
    #[Groups(['api_write'])]
    #[Assert\NotBlank(groups: ['api_write'])]
    private ?string $userName = null;

    /**
     * @OA\Property(description="ID of the user's authentication source")
     */
    #[Groups(['api_write'])]
    #[Assert\NotBlank(groups: ['api_write'])]
    #[Assert\Positive]
    private ?int $authSourceId = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    public function getAuthSourceId(): int
    {
        return $this->authSourceId;
    }

    public function setAuthSourceId(int $authSourceId): self
    {
        $this->authSourceId = $authSourceId;

        return $this;
    }
}
