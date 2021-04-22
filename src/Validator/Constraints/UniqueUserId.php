<?php
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

        if ($this->portalId === null) {
            throw new MissingOptionsException(sprintf('Option "portalId" must be given for constraint %s', __CLASS__), ['portalId']);
        }
    }
}
