<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-01-15
 * Time: 16:36
 */

namespace CommsyBundle\Validator\Constraints;


use Commsy\LegacyBundle\Utils\PortfolioService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniquePortfolioCategoryValidator extends ConstraintValidator
{
    /**
     * @var PortfolioService
     */
    private $portfolioService;

    public function __construct(PortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniquePortfolioCategory) {
            throw new UnexpectedTypeException($constraint, UniquePortfolioCategory::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if ($value === null || $value === '') {
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