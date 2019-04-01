<?php
namespace App\Form\Type;

use App\Form\Type\Custom\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

use App\Services\LegacyEnvironment;

class GeneralSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    private $roomItem;

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
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $this->roomItem = $roomManager->getItem($options['roomId']);

        $builder
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'attr' => array(
                    'style' => 'width: 250px;',
                ),
            ))
            ->add('language', ChoiceType::class, array(
                'choices' => array(
                    'User preferences' => 'user',
                    'German' => 'de',
                    'English' => 'en',
                ),
                'empty_data' => 'user',
                'attr' => array(
                    'style' => 'width: 250px;',
                ),
            ))
            ->add('access_check', ChoiceType::class, array(
                'required' => false,
                'choices' => array(
                    'Never' => 'never',
                    'Always' => 'always',
                    'Code' => 'withcode',
                ),
            ))
            ->add('access_code', TextType::class, array(
                'required' => false,
                'attr' => array(
                    'style' => 'display: none;',
                ),
                'label' => false,
            ))
            ->add('room_description', TextareaType::class, array(
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 90%',
                ),
                'required' => false,
            ))
            ->add('rubrics', CollectionType::class, array(
                'required' => false,
                'entry_type' => ChoiceType::class,
                'entry_options' => array(
                    'choices' => array(
                        'Show' => 'show',
                        'Hide' => 'hide',
                        'Off' => 'off',
                    ),
                ),
                'attr' => array(
                    'class' => 'uk-sortable',
                    'data-uk-sortable' => '',
                ),
            ))
            ->add('rubricOrder', HiddenType::class, array(

            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                )
            ))
        ;

        $roomCategories = $options['roomCategories'];
        if (isset($roomCategories) && !empty($roomCategories)) {
            $builder->add('categories', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => $roomCategories,
                'label' => 'Room categories',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'translation_domain' => 'portal',
            ));
        }

        // TODO: filter room description input (cleanCKEditor)

        // some form fields depend on the underlying data, so we delegate
        // the creation to an event listener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event){
            $form = $event->getForm();

            // check if the room is a community room and
            // add form fields for this case
            if ($this->roomItem->isCommunityRoom()) {
                $form
                    ->add('open_for_guest', CheckboxType::class, array(
                        'label' => 'Is this room open for guests?',
                        'label_attr' => array('class' => 'uk-form-label'),
                        'required' => false
                    ))
                    ->add('material_open_for_guest', CheckboxType::class, array(
                        'label' => 'Are materials open for guests?',
                        'label_attr' => array('class' => 'uk-form-label'),
                        'required' => false
                    ))
                    ->add('assignment_restricted', CheckboxType::class, array(
                        'label' => 'Only members are allow to assign project rooms',
                        'label_attr' => array('class' => 'uk-form-label'),
                        'required' => false
                    ))
                ;
            }

            // check if the room is a project room
            if ($this->roomItem->isProjectRoom()) {
                $choices = $this->getAssignableCommunityRoom();

                if (!empty($choices)) {
                    $form
                        // ->add('community_rooms', 'entity', array(
                        //     'class' => 'App:Room',
                        //     'choices' => $this->getAssignableCommunityRoom(),
                        //     'choice_label' => 'title',
                        //     'multiple' => true,
                        //     'required' => false,
                        // ))
                        ->add('community_rooms', Select2ChoiceType::class, array(
                            'choices' => $choices,
                            'multiple' => true,
                            'required' => false,
                            'attr' => array(
                                'style' => 'width: 90%',
                            ),
                        ))
                    ;
                }
            }

            // check if time intervals are active in portal
            $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
            if ($portalItem->showTime() &&
                ($this->roomItem->isProjectRoom() || $this->roomItem->isGroupRoom()))
            {
                $form
                    ->add('time_pulses', Select2ChoiceType::class, [
                        'label' => $this->getTimeIntervalsDisplayName(),
                        'required' => false,
                        'choices' => $this->getTimeChoices(),
                        'multiple' => true,
                    ])
                ;
            }
        });
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['roomId', 'roomCategories'])
            ->setDefaults(array('translation_domain' => 'settings'))
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
        return 'general_settings';
    }

    /**
     * Returns the display name for time intervals as specified in the current portal configuration
     * for the currently selected language.
     */
    private function getTimeIntervalsDisplayName()
    {
        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $displayName = $currentPortal->getCurrentTimeName();
        if (empty($displayName)) {
            $displayName = 'Time context';
        }

        return $displayName;
    }

    private function getTimeChoices()
    {
        $timeChoices = [];

        $translator = $this->legacyEnvironment->getTranslationObject();

        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portalItem->showTime()) {
            $timeList = $portalItem->getTimeList();
            if ($timeList->isNotEmpty()) {
                $timeItem = $timeList->getFirst();

                while ($timeItem) {
                    $translatedTitle = $translator->getTimeMessage($timeItem->getTitle());
                    $timeChoices[$translatedTitle] = $timeItem->getItemID();

                    $timeItem = $timeList->getNext();
                }
            }
        }

        $timeChoices['continuous'] = 'cont';

        return $timeChoices;
    }

    private function getAssignableCommunityRoom()
    {
        $results = array();

        // query all community rooms
        //$repository = $this->em->getRepository('App:room');
        // $query = $repository->createQueryBuilder('r')
        //     ->andWhere('r.type = :type')
        //     ->setParameter('type', 'community')
        //     ->getQuery();
        // $communityRooms = $query->getResult();
        
        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $communityList = $currentPortal->getCommunityList();

        // iterate all community rooms and check if assignment is only allowed for members
        // $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        // $userManager = $this->legacyEnvironment->getUserManager();
        // $results = array();
        // foreach ($communityRooms as $communityRoom) {
        //     if ($communityRoom->isAssignmentRestricted()) {
        //         $isUser = $userManager->isUserInContext(
        //             $currentUser->getUserID(),
        //             $communityRoom->getItemId(),
        //             $currentUser->getAuthSource()
        //         );

        //         if (!$isUser) {
        //             continue;
        //         }
        //     }

        //     $results[] = $communityRoom;
        // }
        if ($communityList->isNotEmpty()) {
            $communityItem = $communityList->getFirst();
            while ($communityItem) {
                if ($communityItem->isAssignmentOnlyOpenForRoomMembers()) {
                    if (!$communityItem->isUser($currentUser)) {
                        $communityItem = $communityList->getNext();
                        continue;
                    }
                }

                $results[$communityItem->getTitle()] = $communityItem->getItemId();

                $communityItem = $communityList->getNext();
            }
        }

        return $results;
    }
}
