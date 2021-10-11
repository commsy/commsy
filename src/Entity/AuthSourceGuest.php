<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * Class AuthSourceGuest
 * @package App\Entity
 */
class AuthSourceGuest extends AuthSource
{
    /**
     * AuthSourceGuest constructor.
     */
    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
        $this->createRoom = false;
    }

    public function getType(): string
    {
        return 'guest';
    }
}