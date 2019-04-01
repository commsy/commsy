<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class UserPasswordConstraintValidator extends ConstraintValidator
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($password, Constraint $constraint)
    {
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $authentication = $this->legacyEnvironment->getAuthenticationObject();
        $auth_manager = $authentication->getAuthManager($currentUser->getAuthSource());
        $old_password = $auth_manager->getItem($currentUser->getUserID())->getPasswordMD5();
        if($old_password != md5($password)){
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

