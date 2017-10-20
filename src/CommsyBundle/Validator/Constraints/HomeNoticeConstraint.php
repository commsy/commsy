<?php
namespace CommsyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HomeNoticeConstraint extends Constraint
{
    public $message = 'A item of this type ({{ type }}) can not be used as home notice.';
    public $messageNoItem = 'There is not item with this id.';
    public $messageInvalidId = 'The id is invalid.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}