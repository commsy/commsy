<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $this->userItem = $userManager->getItem($options['itemId']);

        $builder
            ->add('firstname', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('lastname', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('userId', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'translation_domain' => 'settings',
                'required' => true,
            ))
            ->add('currentPassword', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('newPassword', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('newPasswordConfirm', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('language', 'choice', array(
                'placeholder' => false,
                'choices'  => array(
                    'browser' => 'Browser',
                    'de' => 'Deutsch',
                    'en' => 'English'
                ),
                'required' => false,
            ))
            ->add('autoSaveStatus', 'checkbox', array(
                'label'    => 'autoSaveStatus',
                'required' => false,
            ))
            
            ->add('combineUserId', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('combinePassword', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            
            ->add('title', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('titleChangeInAllContexts', 'checkbox', array(
                'label'    => 'titleChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('dateOfBirth', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('dateOfBirthChangeInAllContexts', 'checkbox', array(
                'label'    => 'dateOfBirthChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('image', 'file', array(
                'attr' => array(
                    'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'required' => false,
            ))
            ->add('imageChangeInAllContexts', 'checkbox', array(
                'label'    => 'imageChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('email', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('emailChangeInAllContexts', 'checkbox', array(
                'label'    => 'emailChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('isEmailVisible', 'checkbox', array(
                'label'    => 'isEmailVisible',
                'required' => false,
            ))
            ->add('isEmailVisibleChangeInAllContexts', 'checkbox', array(
                'label'    => 'isEmailVisibleChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('phone', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('phoneChangeInAllContexts', 'checkbox', array(
                'label'    => 'phoneChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('mobile', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('mobileChangeInAllContexts', 'checkbox', array(
                'label'    => 'mobileChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('street', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('streetChangeInAllContexts', 'checkbox', array(
                'label'    => 'streetChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('zipcode', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('zipcodeChangeInAllContexts', 'checkbox', array(
                'label'    => 'zipcodeChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('city', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('cityChangeInAllContexts', 'checkbox', array(
                'label'    => 'cityChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('room', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('roomChangeInAllContexts', 'checkbox', array(
                'label'    => 'roomChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('organisation', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('organisationChangeInAllContexts', 'checkbox', array(
                'label'    => 'organisationChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('position', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('positionChangeInAllContexts', 'checkbox', array(
                'label'    => 'positionChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('icq', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('icqChangeInAllContexts', 'checkbox', array(
                'label'    => 'icqChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('msn', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('msnChangeInAllContexts', 'checkbox', array(
                'label'    => 'msnChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('skype', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('skypeChangeInAllContexts', 'checkbox', array(
                'label'    => 'skypeChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('yahoo', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('yahooChangeInAllContexts', 'checkbox', array(
                'label'    => 'yahooChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('jabber', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('jabberChangeInAllContexts', 'checkbox', array(
                'label'    => 'jabberChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('homepage', 'text', array(
                'translation_domain' => 'settings',
                'required' => false,
            ))
            ->add('homepageChangeInAllContexts', 'checkbox', array(
                'label'    => 'homepageChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('description', 'textarea', array(
                'attr' => array('class' => 'tinymce'),
                'required' => false,
            ))
            ->add('descriptionChangeInAllContexts', 'checkbox', array(
                'label'    => 'descriptionChangeInAllContexts',
                'required' => false,
            ))
            
            ->add('newsletterStatus', 'choice', array(
                'placeholder' => false,
                'choices'  => array(
                    '1' => 'none',
                    '2' => 'weekly',
                    '3' => 'daily'
                ),
                'required' => false,
            ))
            
            ->add('widgetStatus', 'checkbox', array(
                'label'    => 'widgetStatus',
                'required' => false,
            ))
            ->add('calendarStatus', 'checkbox', array(
                'label'    => 'calendarStatus',
                'required' => false,
            ))
            ->add('stackStatus', 'checkbox', array(
                'label'    => 'stackStatus',
                'required' => false,
            ))
            ->add('portfolioStatus', 'checkbox', array(
                'label'    => 'portfolioStatus',
                'required' => false,
            ))
            ->add('switchRoomStatus', 'checkbox', array(
                'label'    => 'switchRoomStatus',
                'required' => false,
            ))
            
            ->add('save', 'submit')
            
            ->add('saveCombine', 'submit');   
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array('itemId', 'uploadUrl'))
        ;
    }

    public function getName()
    {
        return 'room_profile';
    }
    
}