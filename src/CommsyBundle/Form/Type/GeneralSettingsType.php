<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityManager;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class GeneralSettingsType extends AbstractType
{
    private $em;
    private $legacyEnvironment;

    public function __construct(EntityManager $em, LegacyEnvironment $legacyEnvironment)
    {
        $this->em = $em;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'translation_domain' => 'settings',
            ))
            ->add('language', 'choice', array(
                'choices' => array(
                    'User' => 'user',
                    'German' => 'de',
                    'English' => 'en',
                ),
                'choices_as_values' => true,
            ))
            ->add('logo', 'file')
            ->add('access_check', 'choice', array(
                'choices' => array(
                    'Never' => 'never',
                    'Always' => 'always',
                    'Code' => 'code',
                ),
                'choices_as_values' => true,
            ))
            ->add('room_description', 'textarea', array(
                'attr' => array(
                    'class' => 'uk-form-width-large',
                ),
                'position' => array(
                    'before' => 'save',
                ),
            ))
            ->add('save', 'submit', array(
                'position' => 'last'
            ));
        ;

        // TODO: feed settings, theme configuration, filter room description input (cleanCKEditor)

        // some form fields depend on the underlying data, so we delegate
        // the creation to an event listener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $room = $event->getData();
            $form = $event->getForm();

            // check if the room is a community room and
            // add form fields for this case
            if ($room->isCommunityRoom()) {
                $form
                    ->add('open_for_guest', 'checkbox', array(
                        'label' => 'Is this room open for guests?',
                        'required' => false
                    ))
                    ->add('material_open_for_guest', 'checkbox', array(
                        'label' => 'Are materials open for guests?',
                        'required' => false
                    ))
                    ->add('assignment_restricted', 'checkbox', array(
                        'label' => 'Only members are allow to assign project rooms',
                        'required' => false
                    ))
                ;
            }

            // check if the room is a project room
            if ($room->isProjectRoom()) {
                $form
                    ->add('community_rooms', 'entity', array(
                        'class' => 'CommsyBundle:Room',
                        'choices' => $this->getAssignableCommunityRoom(),
                        'choice_label' => 'title',
                        'multiple' => true,
                    ))
                ;
            }

            // check if time intervals are active in portal
            $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
            if ($portalItem->showTime()) {
                $form
                    ->add('time_list', 'choice', array(
                        'choices' => $this->getTimeChoices(),
                        'multiple' => true,
                    ))
                ;
            }
        });
    }

    public function getName()
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
        // query all community rooms
        $repository = $this->em->getRepository('CommsyBundle:room');
        $query = $repository->createQueryBuilder('r')
            ->andWhere('r.type = :type')
            ->setParameter('type', 'community')
            ->getQuery();
        $communityRooms = $query->getResult();

        // iterate all community rooms and check if assignment is only allowed for members
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $userManager = $this->legacyEnvironment->getUserManager();
        $results = array();
        foreach ($communityRooms as $communityRoom) {
            if ($communityRoom->isAssignmentRestricted()) {
                $isUser = $userManager->isUserInContext(
                    $currentUser->getUserID(),
                    $communityRoom->getItemId(),
                    $currentUser->getAuthSource()
                );

                if (!$isUser) {
                    continue;
                }
            }

            $results[] = $communityRoom;
        }

        return $results;
    }
}