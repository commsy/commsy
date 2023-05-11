<?php

namespace App\WOPI\Discovery\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class App
{
    #[SerializedName('@name')]
    private string $name;

    #[SerializedName('@favIconUrl')]
    private string $favIconUrl;

    /**
     * @var Action[]
     */
    #[SerializedName('action')]
    private array $actions;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getFavIconUrl(): string
    {
        return $this->favIconUrl;
    }

    public function setFavIconUrl(string $favIconUrl): self
    {
        $this->favIconUrl = $favIconUrl;
        return $this;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param Action[] $actions
     */
    public function setActions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }
}
