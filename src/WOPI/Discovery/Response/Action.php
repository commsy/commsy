<?php

namespace App\WOPI\Discovery\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class Action
{
    #[SerializedName('@name')]
    private string $name;

    #[SerializedName('@default')]
    private bool $default;

    #[SerializedName('@requires')]
    private string $requires;

    #[SerializedName('@urlsrc')]
    private string $urlSrc;

    #[SerializedName('@ext')]
    private string $ext;

    #[SerializedName('@progid')]
    private string $progId;

    #[SerializedName('@newprogid')]
    private string $newProgId;

    #[SerializedName('@newext')]
    private string $newext;

    #[SerializedName('@useparent')]
    private bool $useParent;

    #[SerializedName('@targetext')]
    private string $targetExt;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function getRequires(): string
    {
        return $this->requires;
    }

    public function setRequires(string $requires): self
    {
        $this->requires = $requires;
        return $this;
    }

    public function getUrlSrc(): string
    {
        return $this->urlSrc;
    }

    public function setUrlSrc(string $urlSrc): self
    {
        $this->urlSrc = $urlSrc;
        return $this;
    }

    public function getExt(): string
    {
        return $this->ext;
    }

    public function setExt(string $ext): self
    {
        $this->ext = $ext;
        return $this;
    }

    public function getProgId(): string
    {
        return $this->progId;
    }

    public function setProgId(string $progId): self
    {
        $this->progId = $progId;
        return $this;
    }

    public function getNewProgId(): string
    {
        return $this->newProgId;
    }

    public function setNewProgId(string $newProgId): self
    {
        $this->newProgId = $newProgId;
        return $this;
    }

    public function getNewext(): string
    {
        return $this->newext;
    }

    public function setNewext(string $newext): self
    {
        $this->newext = $newext;
        return $this;
    }

    public function isUseParent(): bool
    {
        return $this->useParent;
    }

    public function setUseParent(bool $useParent): self
    {
        $this->useParent = $useParent;
        return $this;
    }

    public function getTargetExt(): string
    {
        return $this->targetExt;
    }

    public function setTargetExt(string $targetExt): self
    {
        $this->targetExt = $targetExt;
        return $this;
    }
}
