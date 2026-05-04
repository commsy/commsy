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

use cs_room_item;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class MandatoryProjectRoomAssignment extends Constraint
{
    public ?cs_room_item $room = null;

    public string $messageStart = 'Caution, the following project rooms are attached to this workspace:';
    public string $message = '{{ criteria }}';
    public string $messageEnd = 'Those project rooms must be delete first or must be assigned to a different workspace.';

    public function __construct(
        ?cs_room_item $room = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->room = $room;

        if (null === $this->room) {
            throw new MissingOptionsException(sprintf('Option "room" must be given for constraint %s', self::class), ['room']);
        }
    }
}
