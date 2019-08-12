<?php
namespace App\Form\Type\Profile;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use App\Services\LegacyEnvironment;

class ProfileAdditionalType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $userItem;

    public function __construct(EntityManagerInterface $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
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
            ->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices'  => array(
                    'browser' => 'browser',
                    'de' => 'de',
                    'en' => 'en'
                ),
                'label' => 'language',
                'required' => false,
                'empty_data' => 'browser',
            ));
            if ($options['emailToCommsy']) {
                $builder
                    ->add('emailToCommsy', CheckboxType::class, [
                        'label' => 'Activate',
                        'required' => false,
                        'label_attr' => [
                            'class' => 'uk-form-label',
                        ],
                        'translation_domain' => 'settings',
                    ])
                    ->add('emailToCommsySecret', TextType::class, [
                        'label' => 'emailToCommsySecret',
                        'required' => false,
                    ]);
            }

        $builder
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ]
            ]);
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['itemId', 'emailToCommsy'])
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
