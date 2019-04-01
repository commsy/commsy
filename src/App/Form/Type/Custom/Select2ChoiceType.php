<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-02-20
 * Time: 23:54
 */

namespace App\Form\Type\Custom;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2ChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}