<?php


namespace App\Form\Type\Portal;



use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailtextsType extends AbstractType
{

    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userIndexFilterChoice', ChoiceType::class, [
                'choices'  => [
                    'Please choose' => 0,
                    '-----------------' => 14,
                    'Delete user id(s)' => 1,
                    'Lock user id(s)' => 2,
                    'Activate user id(s)' => 3,
                    'Satus moderator' => 4,
                    'Make contact' => 5,
                    'Remove contact' => 6,
                    'Password expires' => 7,
                    'Password is expired' => 8,
                    'Change password' => 9,
                ],
                'required' => true,
                'label' => 'Mailtexts',
                'translation_domain' => 'portal',
            ])
            ->add('contentGerman', TextareaType::class, [
                'label' => 'Content german',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'portal',
            ])
            ->add('resetContentGerman', CheckboxType::class, [
                'label' => 'reset',
                'mapped' => false,
                'required' => false,
            ])
            ->add('contentEnglish', TextType::class, [
                'label' => 'Content english',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'portal',
            ])
            ->add('resetContentEnglish', CheckboxType::class, [
                'label' => 'reset',
                'mapped' => false,
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'portal',
        ]);
    }
}