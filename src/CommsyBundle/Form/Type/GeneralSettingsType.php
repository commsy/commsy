<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

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
                // FIXME: option "translation_domain" invalid for TextType!
                'translation_domain' => 'settings',
            ))
            ->add('language', ChoiceType::class, array(
                'choices' => array(
                    'user' => 'user',
                    'german' => 'de',
                    'english' => 'en',
                ),
            ))
            ->add('room_image', FileType::class, array(
                'attr' => array(
                    'data-upload' => '{"path": "' . $options['uploadUrl'] . '"}',
                ),
                'required' => false,
                //'image_path' => 'webPath',
                // ist jetzt in resolver->setDefaults (s.u.
                //'translation_domain' => 'settings',
            ))
            ->add('access_check', ChoiceType::class, array(
                'choices' => array(
                    'never' => 'never',
                    'always' => 'always',
                    'code' => 'code',
                ),
                // ist jetzt in resolver->setDefaults (s.u.)
                //'translation_domain' => 'settings',
            ))
            ->add('code', TextType::class,
            ->add('room_description', TextareaType::class, array(
                'attr' => array(
                    'class' => 'uk-form-width-large',
                ),
                'position' => array(
                    'before' => 'save',
                ),
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'position' => 'last',
    	    ))
            ->add('rubrics', CollectionType::class, array(
                'entry_type' => ChoiceType::class,
                'entry_options' => array(
                    'choices' => array(
                        'show' => 'show',
                        'hide' => 'hide',
                        'off' => 'off',
                    ),
                    // nicht durch "resolver->setDefaults" abgedeckt, daher hier explizit gesetzt!
                    'translation_domain' => 'settings',
                    //'expanded' => true,
                    //'multiple' => false,
                    'attr' => array(
                        'class' => 'uk-panel-box',
                    )
                ),
                // FIXME: option "translation_domain" invalid for CollectionType!
                'translation_domain' => 'rubric',
                'attr' => array(
                    'class' => 'uk-sortable',
                    'data-uk-sortable' => '',
                ),
            ))
        ;

        // TODO: feed settings, theme configuration, filter room description input (cleanCKEditor)

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
                        'required' => false
                    ))
                    ->add('material_open_for_guest', CheckboxType::class, array(
                        'label' => 'Are materials open for guests?',
                        'required' => false
                    ))
                    ->add('assignment_restricted', CheckboxType::class, array(
                        'label' => 'Only members are allow to assign project rooms',
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
                        //     'class' => 'CommsyBundle:Room',
                        //     'choices' => $this->getAssignableCommunityRoom(),
                        //     'choice_label' => 'title',
                        //     'multiple' => true,
                        //     'required' => false,
                        // ))
                        ->add('community_rooms', ChoiceType::class, array(
                            'choices' => $choices,
                            'multiple' => true,
                            'required' => false,
                        ))
                    ;
                }
            }

            // check if time intervals are active in portal
            $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
            if ($portalItem->showTime()) {
                $form
                    ->add('time_list', ChoiceType::class, array(
                        'choices' => $this->getTimeChoices(),
                        'multiple' => true,
                    ))
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
            ->setRequired(['roomId', 'uploadUrl'])
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

    private function getTimeChoices()
    {
        return array();

        // if($portal_item->showTime()) {
        //         $current_time_title = $portal_item->getTitleOfCurrentTime();

        //         if(isset($current_context)) {
        //             $time_list = $current_context->getTimeList();

        //             if($time_list->isNotEmpty()) {
        //                 $time_item = $time_list->getFirst();
        //                 $linked_time_title = $time_item->getTitle();
        //             }
        //         }

        //         if(!empty($linked_time_title) && $linked_time_title < $current_time_title) {
        //             $start_time_title = $linked_time_title;
        //         } else {
        //             $start_time_title = $current_time_title;
        //         }
        //         $time_list = $portal_item->getTimeList();

        //         if($time_list->isNotEmpty()) {
        //             $time_item = $time_list->getFirst();

        //             $context_time_list = $current_context->getTimeList();

        //             while($time_item) {
        //                 // check if checked
        //                 $checked = false;
        //                 if($context_time_list->isNotEmpty()) {
        //                     $context_time_item = $context_time_list->getFirst();

        //                     while($context_time_item) {
        //                         if($context_time_item->getItemID() === $time_item->getItemID()) {
        //                             $checked = true;
        //                             break;
        //                         }

        //                         $context_time_item = $context_time_list->getNext();
        //                     }
        //                 }

        //                 if($time_item->getTitle() >= $start_time_title) {
        //                     $this->_time_array[] = array(
        //                         'text'      => $translator->getTimeMessage($time_item->getTitle()),
        //                         'value'     => $time_item->getItemID(),
        //                         'checked'   => $checked
        //                     );
        //                 }

        //                 $time_item = $time_list->getNext();
        //             }

        //             // continuous
        //             $this->_time_array[] = array(
        //                 'text'      => $translator->getMessage('COMMON_CONTINUOUS'),
        //                 'value'     => 'cont',
        //                 'checked'   => $current_context->isContinuous()
        //             );
        //         }
        //     }
    }

    private function getAssignableCommunityRoom()
    {
        $results = array();

        // query all community rooms
        //$repository = $this->em->getRepository('CommsyBundle:room');
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
