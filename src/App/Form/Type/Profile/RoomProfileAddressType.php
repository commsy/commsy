<?php
namespace App\Form\Type\Profile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Form\Type\Custom\DateSelectType;

use Doctrine\ORM\EntityManager;

use App\Services\LegacyEnvironment;

class RoomProfileAddressType extends AbstractType
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

        $builder
            ->add('title', TextType::class, array(
                'label' => 'title',
                'required' => false,
            ))
            ->add('titleChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
   
            ->add('street', TextType::class, array(
                'label'    => 'street',
                'required' => false,
            ))
            ->add('streetChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('zipcode', TextType::class, array(
                'label'    => 'zipcode',
                'required' => false,
            ))
            ->add('zipcodeChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('city', TextType::class, array(
                'label'    => 'city',
                'required' => false,
            ))
            ->add('cityChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('room', TextType::class, array(
                'label'    => 'room',
                'required' => false,
            ))
            ->add('roomChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('organisation', TextType::class, array(
                'label'    => 'organisation',
                'required' => false,
            ))
            ->add('organisationChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'data' => true,
            ))
            
            ->add('position', TextType::class, array(
                'label'    => 'position',
                'required' => false,
            ))
            ->add('positionChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
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
        return 'room_profile_address';
    }
    
}
