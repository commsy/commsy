<?php

namespace App\Validator\Constraints;

use App\Repository\RoomRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueRoomSlugValidator extends ConstraintValidator
{
    /** @var RoomRepository $roomRepository */
    private RoomRepository $roomRepository;

    public function __construct(RoomRepository $roomRepository)
    {
        $this->roomRepository = $roomRepository;
    }

    /**
     * Checks if the passed room slug is unique (i.e., if it doesn't already exist for another room in the database).
     *
     * @param mixed $roomSlug The room slug that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($roomSlug, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueRoomSlug) {
            throw new UnexpectedTypeException($constraint, UniqueRoomSlug::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if ($roomSlug === null || $roomSlug === '') {
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
