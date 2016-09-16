<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;

class TodoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('steps', CollectionType::class, array(
                'required' => false,
                'entry_type' => TextType::class,
                'entry_options' => array(
                    'attr' => array(
                        'class' => 'uk-panel-box',
                    ),
                ),
                'attr' => array(
                    'class' => 'uk-sortable',
                    'data-uk-sortable' => '',
                ),
            ))
            ->add('stepOrder', HiddenType::class, array(
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
            ))
        ;
        
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array('translation_domain' => 'form'))
        ;
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
        return 'todo';
    }
}