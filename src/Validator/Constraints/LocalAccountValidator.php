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
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate($localAccount, Constraint $constraint): void
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

        if (null === $localAccount) {
            $this->context->buildViolation($constraint->message)
                ->atPath('username')
                ->addViolation();
        }
    }
}
