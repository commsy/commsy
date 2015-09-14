<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Doctrine\ORM\EntityRepository;

class HashtagType extends AbstractType
{
    public function getParent()
    {
        return 'filter_entity';
    }

    public function getName()
    {
        return 'hashtag';
    }
}