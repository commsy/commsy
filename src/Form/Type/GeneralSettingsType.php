<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Type\Custom\Select2ChoiceType;
use App\Services\LegacyEnvironment;
use App\Validator\Constraints\UniqueRoomSlug;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class GeneralSettingsType extends AbstractType
{
    private \cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($options['roomId']);

        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()], 'attr' => ['style' => 'width: 250px;']])
            ->add('language', ChoiceType::class, ['choices' => ['User preferences' => 'user', 'German' => 'de', 'English' => 'en'], 'empty_data' => 'user', 'attr' => ['style' => 'width: 250px;'], 'help' => 'Room language tip'])
            ->add('access_check', ChoiceType::class, ['required' => false, 'choices' => ['Never' => 'never', 'Always' => 'always', 'Code' => 'withcode']])
            ->add('access_code', TextType::class, ['required' => false, 'attr' => ['style' => 'display: none;'], 'label' => false])
            ->add('room_description', TextareaType::class, ['attr' => ['class' => 'uk-form-width-large', 'style' => 'width: 90%'], 'required' => false])
            ->add('room_slug', TextType::class, [
                'required' => false,
                'constraints' => [
                    new UniqueRoomSlug([
                        'roomItem' => $roomItem,
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Your workspace identifier must not exceed {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[[:alnum:]~._-]+$/', // unreserved URI chars only: any alphanumeric chars plus: ~._-
                        'message' => 'Your workspace identifier may only contain lowercase English letters, digits or any of these special characters: -._~',
                    ]),
                ],
                'attr' => ['class' => 'uk-form-width-large', 'style' => 'width: 90%'],
                'label' => 'Room slug',
            ])
            ->add('rubrics', CollectionType::class, ['required' => false, 'entry_type' => ChoiceType::class, 'entry_options' => ['choices' => ['Show' => 'show', 'Hide' => 'hide', 'Off' => 'off']], 'attr' => ['class' => 'uk-sortable', 'data-uk-sortable' => '']])
            ->add('rubricOrder', HiddenType::class, [])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'uk-button-primary']])
        ;

        $roomCategories = $options['roomCategories'];
        if (isset($roomCategories) && !empty($roomCategories)) {
            $builder->add('categories', ChoiceType::class, ['placeholder' => false, 'choices' => $roomCategories, 'label' => 'Room categories', 'required' => false, 'expanded' => true, 'multiple' => true, 'translation_domain' => 'portal']);
        }

        // TODO: filter room description input (cleanCKEditor)

        // some form fields depend on the underlying data, so we delegate
        // the creation to an event listener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($roomItem) {
            $form = $event->getForm();

            // check if the room is a community room and
            // add form fields for this case
            if ($roomItem->isCommunityRoom()) {
                $form
                    ->add('open_for_guest', CheckboxType::class, ['label' => 'Is this room open for guests?', 'label_attr' => ['class' => 'uk-form-label'], 'required' => false])
                    ->add('material_open_for_guest', CheckboxType::class, ['label' => 'Are materials open for guests?', 'label_attr' => ['class' => 'uk-form-label'], 'required' => false])
                    ->add('assignment_restricted', CheckboxType::class, ['label' => 'Only members are allow to assign project rooms', 'label_attr' => ['class' => 'uk-form-label'], 'required' => false])
                ;
            }

            // check if the room is a project room
            if ($roomItem->isProjectRoom()) {
                $choices = $this->getAssignableCommunityRoom();

                if (!empty($choices)) {
                    $form
                        ->add('community_rooms', Select2ChoiceType::class, ['choices' => $choices, 'multiple' => true, 'required' => false, 'attr' => ['style' => 'width: 90%'], 'help' => 'Community rooms tip'])
                    ;
                }
            }

            // check if time intervals are active in portal
            $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
            if ($portalItem->showTime() &&
                ($roomItem->isProjectRoom() || $roomItem->isGroupRoom())) {
                $form
                    ->add('time_pulses', Select2ChoiceType::class, [
                        'label' => ucfirst($this->getTimeIntervalsDisplayName()),
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
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['roomId', 'roomCategories'])
            ->setDefaults(['translation_domain' => 'settings'])
        ;
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
        $results = [];

        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $communityList = $currentPortal->getCommunityList();
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
