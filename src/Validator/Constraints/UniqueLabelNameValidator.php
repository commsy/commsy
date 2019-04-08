<?php
namespace App\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueLabelNameValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->em = $entityManager;
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

        $repository = $this->em->getRepository('CommsyBundle:Labels');

        $labels = $repository->findLabelsByContextIdAndNameAndType(
            $entity->getContextId(),
            $entity->getName(),
            $entity->getType());

        if ($labels) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}