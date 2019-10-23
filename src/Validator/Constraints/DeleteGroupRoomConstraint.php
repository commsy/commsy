<?php


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class DeleteGroupRoomConstraint extends Constraint
{
    /**
     * @var \cs_room_item
     */
    public $room;

    public $messageStart = 'Caution, the following project rooms are attached to this workspace:';
    public $message = '{{ criteria }}';
    public $messageEnd = 'Those project rooms must be delete first or must be assigned to a different workspace.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($this->room === null) {
            throw new MissingOptionsException(sprintf('Option "room" must be given for constraint %s', __CLASS__), ['portfolioId']);
        }
    }
}