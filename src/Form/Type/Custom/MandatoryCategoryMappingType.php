<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use App\Form\Type\TreeChoiceType;

use Symfony\Component\Validator\Constraints\Count;

use Symfony\Component\OptionsResolver\OptionsResolver;

class MandatoryCategoryMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('categories', TreeChoiceType::class, array(
                'placeholder' => false,
                'choices' => $options['categories'],
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'constraints' => array(
                    new Count(array('min' => 1, 'minMessage' => "Please select at least one category")),
                ),
            ));
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['categories'])
            ->setDefaults(array('translation_domain' => 'form'))
        ;
    }

}
