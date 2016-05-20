<?php
namespace CommsyBundle\Form\Type;

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

use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class RoomProfileType extends AbstractType
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
            ->add('firstname', TextType::class, array(
                'label' => 'firstname',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('lastname', TextType::class, array(
                'label' => 'lastname',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('userId', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'userId',
                'translation_domain' => 'profile',
                'required' => true,
            ))
            ->add('currentPassword', TextType::class, array(
                'label' => 'currentPassword',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('newPassword', TextType::class, array(
                'label' => 'newPassword',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('newPasswordConfirm', TextType::class, array(
                'label' => 'newPasswordConfirm',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices'  => array(
                    'browser' => 'Browser',
                    'de' => 'Deutsch',
                    'en' => 'English'
                ),
                'label' => 'language',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('autoSaveStatus', CheckboxType::class, array(
                'label'    => 'autoSaveStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('title', TextType::class, array(
                'label' => 'title',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('titleChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('dateOfBirth', TextType::class, array(
                'label'    => 'dateOfBirth',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('dateOfBirthChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('image', FileType::class, array(
                'attr' => array(
                    'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'label'    => 'image',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('imageChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('email', TextType::class, array(
                'label'    => 'email',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('emailChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('isEmailVisible', CheckboxType::class, array(
                'label'    => 'isEmailVisible',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('isEmailVisibleChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('phone', TextType::class, array(
                'label'    => 'phone',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('phoneChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('mobile', TextType::class, array(
                'label'    => 'mobile',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('mobileChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('street', TextType::class, array(
                'label'    => 'street',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('streetChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('zipcode', TextType::class, array(
                'label'    => 'zipcode',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('zipcodeChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('city', TextType::class, array(
                'label'    => 'city',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('cityChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('room', TextType::class, array(
                'label'    => 'room',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('roomChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('organisation', TextType::class, array(
                'label'    => 'organisation',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('organisationChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('position', TextType::class, array(
                'label'    => 'position',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('positionChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('icq', TextType::class, array(
                'label'    => 'icq',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('icqChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('msn', TextType::class, array(
                'label'    => 'msn',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('msnChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'translation_domain' => 'profile',
            ))
            
            ->add('skype', TextType::class, array(
                'label'    => 'skype',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('skypeChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('yahoo', TextType::class, array(
                'label'    => 'yahoo',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('yahooChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('jabber', TextType::class, array(
                'label'    => 'jabber',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('jabberChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('homepage', TextType::class, array(
                'label'    => 'homepage',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('homepageChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('description', TextareaType::class, array(
                'attr' => array('cols' => '80', 'rows' => '10'),
                'label'    => 'description',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('descriptionChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'descriptionChangeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('newsletterStatus', ChoiceType::class, array(
                'placeholder' => false,
                'choices'  => array(
                    '1' => 'none',
                    '2' => 'weekly',
                    '3' => 'daily'
                ),
                'label'    => 'newsletterStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('widgetStatus', CheckboxType::class, array(
                'label'    => 'widgetStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('calendarStatus', CheckboxType::class, array(
                'label'    => 'calendarStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('stackStatus', CheckboxType::class, array(
                'label'    => 'stackStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('portfolioStatus', CheckboxType::class, array(
                'label'    => 'portfolioStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('switchRoomStatus', CheckboxType::class, array(
                'label'    => 'switchRoomStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('save', SubmitType::class, array(
                'label' => 'save',
                'translation_domain' => 'form',
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
            ->setRequired(['itemId', 'uploadUrl'])
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