<?php


namespace App\Validator\Constraints;


use App\Entity\Account;
use App\Entity\AuthSourceLocal;
use App\Form\Model\LocalAccount as LocalAccountModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class LocalAccountValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($localAccount, Constraint $constraint)
    {
        if (!$constraint instanceof LocalAccount) {
            throw new UnexpectedTypeException($constraint, LocalAccount::class);
        }

        if (!$localAccount instanceof LocalAccountModel) {
            throw new UnexpectedValueException($localAccount, LocalAccount::class);
        }

        $localSource = $this->entityManager->getRepository(AuthSourceLocal::class)
            ->findOneBy([
                'portal' => $localAccount->getContextId(),
                'enabled' => 1,
            ]);
        $localAccount = $this->entityManager->getRepository(Account::class)
            ->findOneByCredentials(
                $localAccount->getUsername(),
                $localAccount->getContextId(),
                $localSource
            );

        if ($localAccount === null) {
            $this->context->buildViolation($constraint->message)
                ->atPath('username')
                ->addViolation();
        }
    }
}