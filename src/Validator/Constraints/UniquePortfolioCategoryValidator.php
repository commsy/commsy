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

use App\Utils\PortfolioService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniquePortfolioCategoryValidator extends ConstraintValidator
{
    public function __construct(private PortfolioService $portfolioService)
    {
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniquePortfolioCategory) {
            throw new UnexpectedTypeException($constraint, UniquePortfolioCategory::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $tagId = $value[0];

        $currentPortfolioTags = $this->portfolioService->getPortfolioTags($constraint->portfolioId);

        foreach ($currentPortfolioTags as $currentPortfolioTag) {
            if ($currentPortfolioTag['t_id'] == $tagId) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
