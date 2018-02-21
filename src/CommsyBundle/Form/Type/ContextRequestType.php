<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ContextRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['checkNewMembersWithCode']) {
            $builder
                ->add('code', TextType::class, [
                    'constraints' => [
                        new Constraints\EqualTo([
                            'value' => $options['checkNewMembersWithCode'],
                            'message' => 'Your access code is invalid.',
                        ]),
                    ],
                    'label' => 'Code',
                    'attr' => [
                    ],
                    'translation_domain' => 'room',
                    'required' => false,
                ])
                ->add('coderequest', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                    ],
                    'label' => 'become member',
                    'translation_domain' => 'room',
                ])
                ->add('codecancel', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-secondary',
                        'formnovalidate' => '',
                    ],
                    'label' => 'cancel',
                    'translation_domain' => 'form',
                    'validation_groups' => false,
                ])
            ;
        } else {
            $builder
                ->add('description', TextareaType::class, [
                    'label' => 'description',
                    'attr' => [
                        'rows' => 5,
                        'cols' => 80,
                    ],
                    'translation_domain' => 'room',
                    'required' => false,
                ])
                ->add('request', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                    ],
                    'label' => 'become member',
                    'translation_domain' => 'room',
                ])
                ->add('cancel', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-secondary',
                        'formnovalidate' => '',
                    ],
                    'label' => 'cancel',
                    'translation_domain' => 'form',
                    'validation_groups' => false,
                ])
            ;
        }

        if ($options['withAGB']) {
            $builder
                ->add('agb', CheckboxType::class, [
                    'constraints' => [
                        new Constraints\IsTrue([
                            'message' => 'You must accept room agb.',
                        ]),
                    ],
                    'label' => 'AGB',
                    'attr' => [
                    ],
                    'translation_domain' => 'room',
                    'required' => true,
                ])
            ;
        }
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['checkNewMembersWithCode', 'withAGB'])
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
        return 'request';
    }
}