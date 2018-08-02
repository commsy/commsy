<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class InvitationsSettingsType extends AbstractType
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
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($options['roomId']);

        $builder
            ->add('email', TextType::class, array(
                'label' => 'email',
                'attr' => array(
                    'placeholder' => 'Email-adrress of new invitee',
                    'class' => 'uk-form-width-medium',
                ),
                'required' => false,
            ))
            ->add('remove_invitees', ChoiceType::class, array(
                'choices' => $options['invitees'],
                'multiple' => true,
                'expanded' => true
            ))
            ->add('send', SubmitType::class, array(
                'label' => 'Send',
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
            ))
            ;
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
            ->setRequired(['roomId'])
            ->setDefaults(array('translation_domain' => 'settings'))
            ->setRequired(['invitees'])
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
        return 'invitations_settings';
    }
}