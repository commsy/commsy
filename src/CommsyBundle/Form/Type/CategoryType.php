<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class CategoryType extends AbstractType
{
    public function getParent()
    {
        return 'filter_choice';
    }

    public function getName()
    {
        return 'category';
    }
}