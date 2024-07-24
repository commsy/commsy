<?php

namespace App\WOPI\REST;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CheckFileInfoResponse
{
    /**
     * The string name of the file, including extension, without a path.
     * Used for display in user interface (UI), and determining the extension of the file.
     */
    #[SerializedName('BaseFileName')]
    private string $baseFileName;

    /**
     * A string that uniquely identifies the owner of the file.
     * In most cases, the user who uploaded or created the file is considered the owner.
     */
    #[SerializedName('OwnerId')]
    private string $ownerId;

    /**
     * The size of the file in bytes.
     */
    #[SerializedName('Size')]
    private int $size;

    /**
     * A string value uniquely identifying the user currently accessing the file.
     */
    #[SerializedName('UserId')]
    private string $userId;

    /**
     * The current version of the file based on the server's file version schema, as a string.
     * This value must change when the file changes, and version values must never repeat for a given file.
     */
    #[SerializedName('Version')]
    private string $version;

    /**
     * A string that is the name of the user, suitable for displaying in UI.
     */
    #[SerializedName('UserFriendlyName')]
    private string $userFriendlyName;

    /**
     * A Boolean value that indicates that, for this user, the file cannot be changed.
     */
    #[SerializedName('ReadOnly')]
    private bool $readOnly;

    /**
     * A Boolean value that indicates that the user has permission to alter the file.
     * Setting this to true tells the WOPI client that it can call PutFile on behalf of the user.
     */
    #[SerializedName('UserCanWrite')]
    private bool $userCanWrite;

    #[SerializedName('UserCanNotWriteRelative')]
    private bool $userCanNotWriteRelative;

    #[SerializedName('SupportsLocks')]
    private bool $supportsLocks;

    #[SerializedName('SupportsUpdate')]
    private bool $supportsUpdate;

    public function getBaseFileName(): string
    {
        return $this->baseFileName;
    }

    public function setBaseFileName(string $baseFileName): self
    {
        $this->baseFileName = $baseFileName;
        return $this;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId): self
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getUserFriendlyName(): string
    {
        return $this->userFriendlyName;
    }
    public function setUserFriendlyName(string $userFriendlyName): self
    {
        $this->userFriendlyName = $userFriendlyName;
        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function setReadOnly(bool $readOnly): self
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function isUserCanWrite(): bool
    {
        return $this->userCanWrite;
    }

    public function setUserCanWrite(bool $userCanWrite): self
    {
        $this->userCanWrite = $userCanWrite;
        return $this;
    }

    public function isUserCanNotWriteRelative(): bool
    {
        return $this->userCanNotWriteRelative;
    }

    public function setUserCanNotWriteRelative(bool $userCanNotWriteRelative): self
    {
        $this->userCanNotWriteRelative = $userCanNotWriteRelative;
        return $this;
    }

    public function isSupportsLocks(): bool
    {
        return $this->supportsLocks;
    }

    public function setSupportsLocks(bool $supportsLocks): self
    {
        $this->supportsLocks = $supportsLocks;
        return $this;
    }

    public function isSupportsUpdate(): bool
    {
        return $this->supportsUpdate;
    }

    public function setSupportsUpdate(bool $supportsUpdate): self
    {
        $this->supportsUpdate = $supportsUpdate;
        return $this;
    }
}
