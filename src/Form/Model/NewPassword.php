<?php


namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class NewPassword
{
    /**
     * @SecurityAssert\UserPassword(message="Wrong value for your current password.")
     */
    private $currentPassword;

    /**
     * @Assert\IdenticalTo(propertyPath="passwordConfirm", message="Your password confirmation does not match.")
     * @Assert\NotIdenticalTo(propertyPath="currentPassword", message="Your new password should not be identical to your current one.")
     * @Assert\NotCompromisedPassword()
     */
    private $password;

    private $passwordConfirm;

    /**
     * @return mixed
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * @param mixed $currentPassword
     * @return NewPassword
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @return NewPassword
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPasswordConfirm()
    {
        return $this->passwordConfirm;
    }

    /**
     * @param mixed $passwordConfirm
     * @return NewPassword
     */
    public function setPasswordConfirm($passwordConfirm)
    {
        $this->passwordConfirm = $passwordConfirm;
        return $this;
    }
}