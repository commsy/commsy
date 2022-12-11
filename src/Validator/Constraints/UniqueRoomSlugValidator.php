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

use App\Repository\RoomRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueRoomSlugValidator extends ConstraintValidator
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    /**
     * Checks if the passed room slug is unique (i.e., if it doesn't already exist for another room in the database).
     *
     * @param mixed      $roomSlug   The room slug that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($roomSlug, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueRoomSlug) {
            throw new UnexpectedTypeException($constraint, UniqueRoomSlug::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $roomSlug || '' === $roomSlug) {
            return;
        }

        if (!is_string($roomSlug)) {
            throw new UnexpectedTypeException($roomSlug, 'string');
        }

        $roomItem = $constraint->roomItem;

        if ($roomItem->getSlug() === $roomSlug) {
            // room slug hasn't changed
            return;
        }

        $room = $this->roomRepository->findOneByRoomSlug($roomSlug, $roomItem->getContextId());

        if ($room && $room->getItemId() !== $roomItem->getItemID()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
