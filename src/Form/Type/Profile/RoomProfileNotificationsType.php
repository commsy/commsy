<?php
namespace App\Form\Type\Profile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use App\Services\LegacyEnvironment;

class RoomProfileNotificationsType extends AbstractType
{
    private $legacyEnvironment;

    private $userItem;

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
        $userManager = $this->legacyEnvironment->getUserManager();
        $this->userItem = $userManager->getItem($options['itemId']);

        $builder
            ->add('mail_account', CheckboxType::class, array(
                'label' => 'E-mail at registration',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label'
                ]
            ))
            ->add('mail_room', CheckboxType::class, array(
                'label' => 'E-mail when a workspace is created',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label'
                ]
            ))
            ->add('mail_item_deleted', CheckboxType::class, [
                'label' => 'E-mail when an item is deleted',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label'
                ]
            ])
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
            ->setRequired(['itemId'])
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
        return 'room_profile';
    }
}
