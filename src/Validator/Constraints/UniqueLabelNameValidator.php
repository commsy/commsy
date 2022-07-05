<?php
namespace App\Validator\Constraints;

use App\Repository\LabelRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueLabelNameValidator extends ConstraintValidator
{
    private LabelRepository $labelRepository;

    public function __construct(
        LabelRepository $labelRepository
    ) {
        $this->labelRepository = $labelRepository;
    }

    public function validate($entity, Constraint $constraint)
    {
        // entity must have a context id to be validated
        if (!$entity->getContextId()) {
            throw new ConstraintDefinitionException('Entity must have a context id before validation.');
        }

        // entity must have a type to be validated
        if (!$entity->getType()) {
            throw new ConstraintDefinitionException('Entity must have a type before validation.');
        }

        $labels = $this->labelRepository->findLabelsByContextIdAndNameAndType(
            $entity->getContextId(),
            $entity->getName(),
            $entity->getType());

        if ($labels) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
