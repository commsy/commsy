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

namespace App\Form\Type\Context;

use App\Form\Model\RoomData;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_community_item;
use cs_environment;
use cs_list;
use cs_user_item;
use Generator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly TranslatorInterface $translator,
        private readonly RoomService $roomService,
    ) {
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentPortalItem = $this->legacyEnvironment->getCurrentPortalItem();

        $builder
            ->add('generic', RoomType::class, [
                'label' => false,
            ])
            ->add('masterTemplate', ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function(): array {
                    $templates = $this->roomService->getAvailableTemplates('project');
                    uasort($templates, fn ($a, $b) => $a <=> $b);

                    return $templates;
                }),
                'preferred_choices' => function ($choice) use ($currentPortalItem): bool {
                    // NOTE: `getDefault...TemplateID()` may also return '-1' (if no default template is defined)
                    $defaultId = $currentPortalItem->getDefaultProjectTemplateID();

                    return $choice == $defaultId;
                },
                'placeholder' => 'No template',
                'required' => false,
                'label' => 'Template',
                'getter' => function () use ($currentPortalItem): string {
                    return $currentPortalItem->getDefaultProjectTemplateID() ?: '';
                },
            ])
        ;

        // This will set the default value if the form is submitted with empty values, which
        // is happening if it is embedded in the ContextType form (type select)
        $builder->get('masterTemplate')->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($currentPortalItem): void {
                $data = $event->getData();

                if ($data === null && $currentPortalItem->getDefaultProjectTemplateID()) {
                    $event->setData($currentPortalItem->getDefaultProjectTemplateID());
                }
            }
        );

        $builder->add('communityRooms', ChoiceType::class, [
            'autocomplete' => true,
            'choice_loader' => new CallbackChoiceLoader(function () {
                $currentPortalItem = $this->legacyEnvironment->getCurrentPortalItem();
                $currentUser = $this->legacyEnvironment->getCurrentUserItem();

                $communityManager = $this->legacyEnvironment->getCommunityManager();
                $communityManager->setContextLimit($currentPortalItem->getItemID());
                $communityManager->select();
                $communityList = $communityManager->get();

                function getChoices(cs_list $communityList, cs_user_item $currentUser): Generator
                {
                    foreach ($communityList as $communityRoom) {
                        /** @var cs_community_item $communityRoom */
                        if (false === $communityRoom->isAssignmentOnlyOpenForRoomMembers() ||
                            $communityRoom->isUser($currentUser)) {
                            yield html_entity_decode($communityRoom->getTitle()) => $communityRoom->getItemID();
                        }
                    }
                }

                return [$this->translator->trans('Select some options') => ''] +
                    iterator_to_array(getChoices($communityList, $currentUser));
            }),

            'required' => $currentPortalItem->getProjectRoomLinkStatus() != 'optional',
            'multiple' => true,
            'expanded' => false,
            'label' => 'Community rooms',
            'help' => 'Community rooms tip',
            'translation_domain' => 'settings',
        ])
        ->add('createUserRooms', CheckboxType::class, [
            'label' => 'User room',
            'translation_domain' => 'settings',
            'required' => false,
            'label_attr' => ['class' => 'uk-form-label'],
            'help' => 'User room tooltip',
        ])
        ->add('userroomTemplate', ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function(): array {
                $templates = $this->roomService->getAvailableTemplates('project');
                uasort($templates, fn ($a, $b) => $a <=> $b);

                return $templates;
            }),
            'placeholder' => new TranslatableMessage('No template', [], 'project'),
            'required' => false,
            'label' => 'User room template',
            'translation_domain' => 'settings',
        ]);

        $times = $this->roomService->getTimePulses(true);
        if (!empty($times)) {
            $choices = [$this->translator->trans('Select some options') => ''] + $times;

            $timesDisplay = ucfirst((string) $currentPortalItem->getCurrentTimeName());

            $builder->add('timeInterval', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $choices,
                'required' => false,
                'mapped' => false,
                'expanded' => false,
                'multiple' => true,
                'label' => $timesDisplay,
                'translation_domain' => 'room',
                'constraints' => [
                    new Assert\Callback($this->validateTimePulses(...)),
                ],
            ]);
        }

        $builder
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-default',
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([])
            ->setDefaults([
                'data_class' => RoomData::class,
                'translation_domain' => 'project',
            ]);
    }

    public function validateTimePulses(array $selectedTimePulses, ExecutionContextInterface $context): void
    {
        if (count($selectedTimePulses) > 1 && in_array('cont', $selectedTimePulses)) {
            $context->buildViolation('Continuous can only be specified without any other time pulse choices.')
                ->atPath('timeInterval')
                ->addViolation();
        }
    }
}
