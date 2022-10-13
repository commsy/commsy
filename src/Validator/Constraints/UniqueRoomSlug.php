<?php
namespace App\Validator\Constraints;

use cs_room_item;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class UniqueRoomSlug extends Constraint
{
    /** @var cs_room_item $roomItem */
    public cs_room_item $roomItem;

    public $message = 'A workspace with the same workspace identifier already exists.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->roomItem === null) {
            throw new MissingOptionsException(sprintf('Option "roomItem" must be given for constraint %s', __CLASS__), ['roomItem']);
        }
    }
}
