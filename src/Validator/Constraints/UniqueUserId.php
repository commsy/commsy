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

/**
 * @Annotation
 */
class UniqueUserId extends Constraint
{
    public $portalId;

    public $message = 'A user id with the same name already exists.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null === $this->portalId) {
            throw new MissingOptionsException(sprintf('Option "portalId" must be given for constraint %s', self::class), ['portalId']);
        }
    }
}
