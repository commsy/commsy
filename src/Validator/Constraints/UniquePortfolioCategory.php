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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class UniquePortfolioCategory extends Constraint
{
    public $portfolioId;

    public $message = 'A category can only be used once per portfolio.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null === $this->portfolioId) {
            throw new MissingOptionsException(sprintf('Option "portfolioId" must be given for constraint %s', self::class), ['portfolioId']);
        }
    }
}
