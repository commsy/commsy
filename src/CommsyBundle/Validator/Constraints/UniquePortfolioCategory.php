<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-01-15
 * Time: 16:33
 */

namespace CommsyBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class UniquePortfolioCategory extends Constraint
{
    public $portfolioId;

    public $message = 'A category can only be used once per portfolio.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->portfolioId === null) {
            throw new MissingOptionsException(sprintf('Option "portfolioId" must be given for constraint %s', __CLASS__), ['portfolioId']);
        }
    }
}