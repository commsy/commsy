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

class UniqueUserId extends Constraint
{
    public int $portalId;
    public string $message = 'A user id with the same name already exists.';

    public function __construct(?int $portalId = null, ?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        if ($portalId === null) {
            throw new MissingOptionsException(sprintf('Option "portalId" must be given for constraint %s', self::class), ['portalId']);
        }

        $this->portalId = $portalId;
        $this->message = $message ?? $this->message;

        parent::__construct(null, $groups, $payload);
    }
}
