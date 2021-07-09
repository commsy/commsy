<?php
namespace App\Form\Type\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'choice_label' => function ($choice, $key, $value) {
                    // remove the trailing category ID from $key (which was used in ItemController->transformTagArray() to uniquify the key)
                    $label = implode('_', explode('_', $key, -1));
                    return $label;
                },
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'constraints' => array(
                    new Count(array('min' => 1, 'minMessage' => "Please select at least one category")),
                ),
            ))
            ->add('newCategory', TextType::class, array(
                'attr' => array(
                    'placeholder' => $options['categoryPlaceholderText'],
                ),
                'label' => 'newCategory',
                'required' => false
            ))
            ->add('newCategoryAdd', ButtonType::class, array(
                'attr' => array(
                    'id' => 'addNewCategory',
                    'data-cs-add-category' => $options['categoryEditUrl'],
                ),
                'label' => 'addNewCategory',
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
            ->setRequired(['categoryPlaceholderText', 'categories', 'categoryEditUrl'])
            ->setDefaults(array('translation_domain' => 'form'))
        ;
    }

}
