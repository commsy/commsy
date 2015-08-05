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
                'label' => 'firstname',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('lastname', 'text', array(
                'label' => 'lastname',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('userId', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'userId',
                'translation_domain' => 'profile',
                'required' => true,
            ))
            ->add('currentPassword', 'text', array(
                'label' => 'currentPassword',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('newPassword', 'text', array(
                'label' => 'newPassword',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('newPasswordConfirm', 'text', array(
                'label' => 'newPasswordConfirm',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('language', 'choice', array(
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
            ->add('autoSaveStatus', 'checkbox', array(
                'label'    => 'autoSaveStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('title', 'text', array(
                'label' => 'title',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('titleChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('dateOfBirth', 'text', array(
                'label'    => 'dateOfBirth',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('dateOfBirthChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('image', 'file', array(
                'attr' => array(
                    'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'label'    => 'image',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('imageChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('email', 'text', array(
                'label'    => 'email',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('emailChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('isEmailVisible', 'checkbox', array(
                'label'    => 'isEmailVisible',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('isEmailVisibleChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('phone', 'text', array(
                'label'    => 'phone',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('phoneChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('mobile', 'text', array(
                'label'    => 'mobile',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('mobileChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('street', 'text', array(
                'label'    => 'street',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('streetChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('zipcode', 'text', array(
                'label'    => 'zipcode',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('zipcodeChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('city', 'text', array(
                'label'    => 'city',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('cityChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('room', 'text', array(
                'label'    => 'room',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('roomChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('organisation', 'text', array(
                'label'    => 'organisation',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('organisationChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('position', 'text', array(
                'label'    => 'position',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('positionChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('icq', 'text', array(
                'label'    => 'icq',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('icqChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('msn', 'text', array(
                'label'    => 'msn',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('msnChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'required' => false,
                'translation_domain' => 'profile',
            ))
            
            ->add('skype', 'text', array(
                'label'    => 'skype',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('skypeChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('yahoo', 'text', array(
                'label'    => 'yahoo',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('yahooChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('jabber', 'text', array(
                'label'    => 'jabber',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('jabberChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('homepage', 'text', array(
                'label'    => 'homepage',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('homepageChangeInAllContexts', 'checkbox', array(
                'label'    => 'changeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('description', 'textarea', array(
                'attr' => array('cols' => '80', 'rows' => '10'),
                'label'    => 'description',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('descriptionChangeInAllContexts', 'checkbox', array(
                'label'    => 'descriptionChangeInAllContexts',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('newsletterStatus', 'choice', array(
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
            
            ->add('widgetStatus', 'checkbox', array(
                'label'    => 'widgetStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('calendarStatus', 'checkbox', array(
                'label'    => 'calendarStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('stackStatus', 'checkbox', array(
                'label'    => 'stackStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('portfolioStatus', 'checkbox', array(
                'label'    => 'portfolioStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            ->add('switchRoomStatus', 'checkbox', array(
                'label'    => 'switchRoomStatus',
                'translation_domain' => 'profile',
                'required' => false,
            ))
            
            ->add('save', 'submit');   
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