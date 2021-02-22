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

    public function getType(): string
    {
        return 'guest';
    }

    /**
     * @param bool $guestsMayEnter
     */
    public function setGuestsMayEnter(?bool $guestsMayEnter)
    {
        $extras = $this->getExtras();
        $extras['GUESTS_MAY_ENTER'] = $guestsMayEnter;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function isGuestsMayEnter():? bool
    {
        return $this->getExtras()['GUESTS_MAY_ENTER'] ?? false;
    }
}