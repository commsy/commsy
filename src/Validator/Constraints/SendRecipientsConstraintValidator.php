<?php
namespace App\Validator\Constraints;

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
        if (isset($values['additional_recipients'][0])) {
            $foundRecipient = true;
        }
        if (isset($values['send_to_groups'][0])
            && $values['send_to_groups'][0]) {
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

        if(isset($values['send_to_attendees'])
            && $values['send_to_attendees']){
            $foundRecipient = true;
        }


        if (!$foundRecipient) {
            $this->context->buildViolation($constraint->message)->setParameter('parameter', 'value')->addViolation();
        }
    }
}
