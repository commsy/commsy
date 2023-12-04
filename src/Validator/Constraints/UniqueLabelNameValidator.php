<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Repository\LabelRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueLabelNameValidator extends ConstraintValidator
{
    public function __construct(private readonly LabelRepository $labelRepository)
    {
    }

    public function validate($entity, Constraint $constraint): void
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
