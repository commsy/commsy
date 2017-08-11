<?php
namespace CommsyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class SendRecipientsConstraintValidator extends ConstraintValidator
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($additional_recipients, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('parameter', 'value')
            ->addViolation();
    }
}
