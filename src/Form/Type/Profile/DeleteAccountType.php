<?php
namespace App\Form\Type\Profile;

use App\Validator\Constraints\ModeratorAccountDeleteConstraint;
use App\Validator\Constraints\UniqueModeratorConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Utils\UserService;

class DeleteAccountType extends AbstractType
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
        $builder
            ->add('confirm_field', TextType::class, [
                'label' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\IdenticalTo([
                        'value' => mb_strtoupper($options['data']['confirm_string']),
                        'message' => 'The input does not match {{ compared_value }}'
                    ]),
                    new ModeratorAccountDeleteConstraint(),
                ],
                'required' => true,
            ])
            ->add('confirm_button', SubmitType::class, [
                'label' => 'Confirm',
                'attr' => [
                    'class' => 'uk-button-danger',
                ]
            ])
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
            ->setDefaults([
                'translation_domain' => 'settings'
            ])
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
        return 'profile_delete';
    }
}
