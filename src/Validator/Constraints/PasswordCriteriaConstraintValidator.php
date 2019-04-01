<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use App\Services\LegacyEnvironment;

class PasswordCriteriaConstraintValidator extends ConstraintValidator
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($password, Constraint $constraint)
    {
        $current_portal_item = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if ( !$this->legacyEnvironment->inPortal() )
        {
            $portalUser = $this->legacyEnvironment->getPortalUserItem();
        }
        else
        {
            $portalUser = $this->legacyEnvironment->getCurrentUserItem();
        }

        $translator = $this->legacyEnvironment->getTranslationObject();

        if($current_portal_item->getPasswordGeneration() < 1 ||
           ($current_portal_item->getPasswordGeneration() > 0 && !$portalUser->isPasswordInGeneration(md5($password)))){
            // check password propterties depending on configured password criteria options
            $auth_source_manager = $this->legacyEnvironment->getAuthSourceManager();
            $auth_source_item = $auth_source_manager->getItem($currentUser->getAuthSource());

            if($auth_source_item->getPasswordLength() > 0){
                if(strlen($password) < $auth_source_item->getPasswordLength()) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $translator->getMessage('PASSWORD_INFO_LENGTH',$auth_source_item->getPasswordLength()))
                        ->addViolation();
                }
            }
            if($auth_source_item->getPasswordSecureBigchar() == 1){
                if(!preg_match('~[A-Z]+~u', $password)) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $translator->getMessage('PASSWORD_INFO_BIG'))
                        ->addViolation();
                }
            }
            if($auth_source_item->getPasswordSecureSmallchar() == 1){
                if(!preg_match('~[a-z]+~u', $password)) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $translator->getMessage('PASSWORD_INFO_SMALL'))
                        ->addViolation();
                }
            }
            if($auth_source_item->getPasswordSecureNumber() == 1){
                if(!preg_match('~[0-9]+~u', $password)) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $translator->getMessage('PASSWORD_INFO_NUMBER'))
                        ->addViolation();
                }
            }
            if($auth_source_item->getPasswordSecureSpecialchar() == 1){
                if(!preg_match('~[^a-zA-Z0-9]+~u', $password)){
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $translator->getMessage('PASSWORD_INFO_SPECIAL'))
                        ->addViolation();
                }
            }

        } elseif ($current_portal_item->getPasswordGeneration() > 0 && $portalUser->isPasswordInGeneration(md5($password))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ critiera }}', 'password generation error')
                ->addViolation();
        }
    }
}
