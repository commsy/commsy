<?php
namespace App\Form\Type\Profile;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Services\LegacyEnvironment;

class RoomProfileContactType extends AbstractType
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
            ->add('emailRoom', EmailType::class, array(
                'label'    => 'email',
                'required' => true,
            ))
            ->add('emailAccount', HiddenType::class)
            ->add('emailChoice', ChoiceType::class, array(
                'label'    => false,
                'expanded' => true,
                'multiple' => false,
                'choices'  => [
                    'Account'      => 'account',
                    'Room profile' => 'roomProfile',
                ]
            ))
            ->add('emailChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('hideEmailInThisRoom', CheckboxType::class, array(
                'label'    => 'isEmailVisible',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('hideEmailInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('phone', TextType::class, array(
                'label'    => 'phone',
                'required' => false,
            ))
            ->add('phoneChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('mobile', TextType::class, array(
                'label'    => 'mobile',
                'required' => false,
            ))
            ->add('mobileChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            ->add('skype', TextType::class, array(
                'label'    => 'skype',
                'required' => false,
            ))
            ->add('skypeChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('homepage', TextType::class, array(
                'label'    => 'homepage',
                'required' => false,
            ))
            ->add('homepageChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('description', TextareaType::class, array(
                'attr' => array('cols' => '80', 'rows' => '10'),
                'label'    => 'description',
                'required' => false,
            ))
            ->add('descriptionChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'descriptionChangeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
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
        return 'room_profile_contact';
    }
    
}
