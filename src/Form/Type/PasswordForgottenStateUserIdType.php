<?php


namespace App\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class PasswordForgottenStateUserIdType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('userId', TextType::class, array(
                'label' => 'login.label_username',
                'translation_domain' => 'login',
                'required' => false,
                'constraints' => [new NotBlank()],
            ))
            ->add('cancel', SubmitType::class, array(
                'label' => 'cancel',
                'translation_domain' => 'form',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'send',
                'translation_domain' => 'form',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
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
            ->setRequired([]);
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
        return 'extension_settings';
    }
}