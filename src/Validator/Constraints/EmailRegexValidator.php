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
use App\Repository\TranslationRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailRegexValidator extends ConstraintValidator
{
    public function __construct(private readonly TranslationRepository $translationRepository, private readonly RequestStack $requestStack)
    {
    }

    /**
     * @param Account $account
     *
     * @throws NonUniqueResultException
     */
    public function validate($account, Constraint $constraint): void
    {
        if (!$account instanceof Account) {
            throw new UnexpectedTypeException($constraint, Account::class);
        }

        if (!$constraint instanceof EmailRegex) {
            throw new UnexpectedTypeException($constraint, EmailRegex::class);
        }

        $authSource = $account->getAuthSource();
        if ($authSource instanceof AuthSourceLocal) {
            $regex = $authSource->getMailRegex();

            // check regex
            if ($regex && !preg_match($regex, $account->getEmail())) {
                $locale = $this->requestStack->getCurrentRequest()->getLocale();
                $message = $this->translationRepository->findOneByContextAndKey(
                    $authSource->getPortal()->getId(),
                    'EMAIL_REGEX_ERROR'
                )->getTranslationForLocale($locale);

                $this->context->buildViolation($message)
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }
}
