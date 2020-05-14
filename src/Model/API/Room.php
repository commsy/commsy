<?php


namespace App\Model\API;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class Room
{
    /**
     * @var string
     *
     * @Groups({"api_write"})
     * @SWG\Property(type="string", maxLength=255)
     *
     * @Assert\NotBlank(groups={"api_write"})
     */
    private $title;

    /**
     * @var string
     *
     * @Groups({"api_write"})
     * @SWG\Property(description="Either project or community")
     *
     * @Assert\NotBlank(groups={"api_write"})
     * @Assert\Regex("/^(project|community)$/", groups={"api_write"})
     */
    private $type = 'project';

    /**
     * @var string
     *
     * @Groups({"api_write"})
     */
    private $description;

    /**
     * @var string
     *
     * @Groups({"api_write"})
     * @SWG\Property(description="The username of the room creator")
     *
     * @Assert\NotBlank(groups={"api_write"})
     */
    private $userName;

    /**
     * @var int
     *
     * @Groups({"api_write"})
     * @SWG\Property(description="ID of the user's authentication source")
     *
     * @Assert\NotBlank(groups={"api_write"})
     * @Assert\Positive
     */
    private $authSourceId;

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Room
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Room
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Room
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return Room
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * @return int
     */
    public function getAuthSourceId(): int
    {
        return $this->authSourceId;
    }

    /**
     * @param int $authSourceId
     * @return Room
     */
    public function setAuthSourceId(int $authSourceId): self
    {
        $this->authSourceId = $authSourceId;
        return $this;
    }
}