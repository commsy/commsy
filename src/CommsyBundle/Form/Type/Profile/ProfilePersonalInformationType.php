<?php
namespace CommsyBundle\Form\Type\Profile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class ProfilePersonalInformationType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $userItem;

    public function __construct(EntityManager $em, LegacyEnvironment $legacyEnvironment)
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

        $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
        $authSourceItem = $authSourceManager->getItem($this->userItem->getAuthSource());

        if($authSourceItem->allowChangeUserID()) {
            $disabled = false;
        } else {
            $disabled = true;
        }

        $builder
            ->add('userId', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'userId',
                'required' => true,
                'disabled' => $disabled,
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'firstname',
                'required' => false,
            ))
            ->add('lastname', TextType::class, array(
                'label' => 'lastname',
                'required' => false,
            ))
            ->add('emailAccount', TextType::class, array(
                'label' => 'email',
                'required' => true,
            ))
            ->add('dateOfBirth', DateType::class, array(
                'label'    => 'dateOfBirth',
                'required' => false,
                'format' => 'dd.MM.yyyy',
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                ),
                'widget' => 'single_text',
            ))
            ->add('dateOfBirthChangeInAllContexts', CheckboxType::class, array(
                // 'label'    => 'changeInAllContexts',
                'label'    => false,
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
                'attr' => array(
                    'style' => 'display: none'
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
        return 'personal_information';
    }
    
}
