<?php

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
    /**
     * @var TranslationRepository
     */
    private TranslationRepository $translationRepository;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    public function __construct(
        TranslationRepository $translationRepository,
        RequestStack $requestStack
    ) {
        $this->translationRepository = $translationRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @param Account $account
     * @param Constraint $constraint
     * @return void
     * @throws NonUniqueResultException
     */
    public function validate($account, Constraint $constraint)
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
