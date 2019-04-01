<?php
namespace App\Form\Type\Profile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\NotBlank;

use App\Validator\Constraints\UserPasswordConstraint;
use App\Validator\Constraints\PasswordCriteriaConstraint;

use App\Services\LegacyEnvironment;

class ProfileChangePasswordType extends AbstractType
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

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
            ->add('old_password', PasswordType::class, array(
                'label'    => 'currentPassword',
                'required' => true,
                'constraints' => array(
                    new UserPasswordConstraint(),
                ),
            ))
            ->add('new_password', RepeatedType::class, array(
                'type'            => PasswordType::class,
                'invalid_message' => 'Passwords do not match',
                'label'           => 'newPassword',
                'options'         => array(
                    'required' => true
                ),
                'first_options'   => array(
                    'label'       => 'newPassword',
                    'constraints' => array(
                        new NotBlank(),
                        new PasswordCriteriaConstraint(),
                    ),
                ),
                'second_options'  => array(
                    'label' => 'newPasswordConfirm'
                ),
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
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
            ->setDefaults(array('translation_domain' => 'profile'))
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
        return 'profile_changepassword';
    }
}
