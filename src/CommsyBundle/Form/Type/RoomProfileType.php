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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use CommsyBundle\Form\Type\Custom\DateSelectType;

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
                'required' => false,
            ))
            ->add('lastname', TextType::class, array(
                'label' => 'lastname',
                'required' => false,
            ))
            ->add('userId', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'userId',
                'required' => true,
            ))
            ->add('currentPassword', TextType::class, array(
                'label' => 'currentPassword',
                'required' => false,
            ))
            ->add('newPassword', TextType::class, array(
                'label' => 'newPassword',
                'required' => false,
            ))
            ->add('newPasswordConfirm', TextType::class, array(
                'label' => 'newPasswordConfirm',
                'required' => false,
            ))
            ->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices'  => array(
                    'Browser' => 'browser',
                    'Deutsch' => 'de',
                    'English' => 'en'
                ),
                'label' => 'language',
                'required' => false,
            ))
            ->add('autoSaveStatus', CheckboxType::class, array(
                'label'    => 'autoSaveStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            
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
            ))
            
            ->add('dateOfBirth', DateSelectType::class, array(
                'label'    => 'dateOfBirth',
                'required' => false,
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
            
            ->add('image', FileType::class, array(
                'attr' => array(
                    'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'label'    => 'image',
                'required' => false,
            ))
            ->add('image_data', HiddenType::class, array(
            ))
            ->add('imageChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            
            ->add('email', TextType::class, array(
                'label'    => 'email',
                'required' => false,
            ))
            ->add('emailChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
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
            ))
            
            ->add('icq', TextType::class, array(
                'label'    => 'icq',
                'required' => false,
            ))
            ->add('icqChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            
            ->add('msn', TextType::class, array(
                'label'    => 'msn',
                'required' => false,
            ))
            ->add('msnChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
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
            ))
            
            ->add('yahoo', TextType::class, array(
                'label'    => 'yahoo',
                'required' => false,
            ))
            ->add('yahooChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            
            ->add('jabber', TextType::class, array(
                'label'    => 'jabber',
                'required' => false,
            ))
            ->add('jabberChangeInAllContexts', CheckboxType::class, array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
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
            ))
            
            ->add('newsletterStatus', ChoiceType::class, array(
                'placeholder' => false,
                'expanded' => true,
                'multiple' => false,
                'choices'  => array(
                    'none' => '1',
                    'weekly' => '2',
                    'daily' => '3'
                ),
                'label'    => 'newsletterStatus',
                'required' => false,
            ))
            
            ->add('widgetStatus', CheckboxType::class, array(
                'label'    => 'widgetStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('calendarStatus', CheckboxType::class, array(
                'label'    => 'calendarStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('stackStatus', CheckboxType::class, array(
                'label'    => 'stackStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('portfolioStatus', CheckboxType::class, array(
                'label'    => 'portfolioStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('switchRoomStatus', CheckboxType::class, array(
                'label'    => 'switchRoomStatus',
                'required' => false,
                'label_attr' => array(
                    'class' => 'uk-form-label',
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
            ->setRequired(['itemId', 'uploadUrl'])
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