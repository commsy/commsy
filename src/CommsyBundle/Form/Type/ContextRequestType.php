<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;

class ContextRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, array(
                'label' => 'description',
                'attr' => array(
                ),
                'translation_domain' => 'room',
                'required' => false,
            ))
        ;
        if (isset($options['checkNewMembersWithCode'])) {
            $builder
                ->add('code', TextType::class, array(
                    'label' => 'code',
                    'attr' => array(
                    ),
                    'translation_domain' => 'room',
                    'required' => false,
                ))
            ;
        }
        if (isset($options['withAGB'])) {
            $builder
                ->add('agb', CheckboxType::class, array(
                    'label' => 'agb',
                    'attr' => array(
                    ),
                    'translation_domain' => 'room',
                    'required' => false,
                ))
            ;
        }
        $builder
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'become member',
                'translation_domain' => 'room',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
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
            ->setRequired([])
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
        return 'project';
    }
}