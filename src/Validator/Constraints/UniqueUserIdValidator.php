<?php
namespace App\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserIdValidator extends ConstraintValidator
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Checks if the passed user ID is unique (i.e., if it doesn't already exist in the database).
     *
     * @param mixed $userId The user ID that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($userId, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueUserId) {
            throw new UnexpectedTypeException($constraint, UniqueUserId::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if ($userId === null || $userId === '') {
            return;
        }

        if (!is_string($userId)) {
            throw new UnexpectedTypeException($userId, 'string');
        }

        $repository = $this->entityManager->getRepository('App:Auth');

        $auth = $repository->findOneBy([
            'commsyId' => $constraint->portalId,
            'userId' => $userId,
        ]);

        if ($auth) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
