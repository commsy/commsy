<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TreeChoiceType extends AbstractType
{
    /**
     * Returns the name of the parent type.
     * 
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    
    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'treechoice';
    }
}