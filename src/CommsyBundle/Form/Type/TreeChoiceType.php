<?php

namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TreeChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'choice';
    }
    
    public function getName()
    {
        return 'treechoice';
    }
}