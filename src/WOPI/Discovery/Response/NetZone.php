<?php

namespace App\WOPI\Discovery\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class NetZone
{
    #[SerializedName('@name')]
    private WOPIZone $name;

    /**
     * @var App[]
     */
    #[SerializedName('app')]
    private array $apps;
    public function getName(): WOPIZone
    {
        return $this->name;
    }

    public function setName(WOPIZone $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return App[]
     */
    public function getApps(): array
    {
        return $this->apps;
    }

    /**
     * @param App[] $apps
     */
    public function setApps(array $apps): self
    {
        $this->apps = $apps;
        return $this;
    }
}
