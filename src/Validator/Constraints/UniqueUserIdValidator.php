<?php

namespace App\Validator\Constraints;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserIdValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface $entityManager */
    private EntityManagerInterface $entityManager;

    /**
     * @var Security
     */
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
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

        /** @var Account $user */
        $user = $this->security->getUser();
        if ($user) {
            if ($user->getUsername() === $userId) {
                // user ID hasn't changed
                return;
            }

            $accountRepository = $this->entityManager->getRepository(Account::class);
            $lookup = $accountRepository->findOneByCredentials($userId, $user->getContextId(), $user->getAuthSource());

            if ($lookup) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
