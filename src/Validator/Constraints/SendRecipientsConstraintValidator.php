<?php
namespace App\Validator\Constraints;

use App\Form\Model\Send;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use App\Services\LegacyEnvironment;

class SendRecipientsConstraintValidator extends ConstraintValidator
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($additional_recipients, Constraint $constraint)
    {
        $values = $this->context->getRoot()->getData();
        $foundRecipient = false;

        if(get_class($values) == Send::class){
            if(sizeof($values->getAdditionalRecipients()) > 0 && isset($values->getAdditionalRecipients()[0])){
                $foundRecipient = true;
            }

            $isSendToGroupAll = $values->getSendToGroupAll();

            if(!isset($isSendToGroupAll)){
                $isSendToGroupAll = true;
            }

            if(sizeof($values->getSendToGroups()) > 0 && isset($values->getSendToGroups()[0]) && $isSendToGroupAll){
                    $foundRecipient = true;
            }
            if($values->getSendToGroupAll()){
                $foundRecipient = true;
            }
            if($values->getSendToAll()){
                $foundRecipient = true;
            }
            if($values->getCopyToSender()){
                $foundRecipient = true;
            }
            if(!is_null($values->getSendToAttendees())){
                $foundRecipient = true;
            }
        }else{
            if (isset($values['additional_recipients'][0])) {
                $foundRecipient = true;
            }
            if (isset($values['send_to_groups'][0])) {
                $foundRecipient = true;
            }
            if ($values['send_to_group_all']) {
                $foundRecipient = true;
            }
            if ($values['send_to_all']) {
                $foundRecipient = true;
            }
            if ($values['copy_to_sender']) {
                $foundRecipient = true;
            }
            if(isset($values['send_to_attendees'])){
                if ($values['send_to_attendees']) {
                    $foundRecipient = true;
                }
            }
        }

        if (!$foundRecipient) {
            $this->context->buildViolation($constraint->message)->setParameter('parameter', 'value')->addViolation();
        }
    }
}
