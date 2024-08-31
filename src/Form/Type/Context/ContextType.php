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

use App\Form\Model\ContextCreateData;
use App\Form\Model\RoomData;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ContextType extends AbstractType
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly LegacyEnvironment $legacyEnvironment
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $builder
            ->add('type_select', ChoiceType::class, [
                'placeholder' => false,
                'choice_loader' => new CallbackChoiceLoader(static function() use ($legacyEnvironment, $options): array {
                    $currentUser = $legacyEnvironment->getCurrentUserItem();
                    $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();
                    $portalUser = $currentUser->getRelatedPortalUserItem();

                    $types = [];
                    if ($portalUser->isModerator()) {
                        $types = ['project' => 'project', 'community' => 'community'];
                    } else {
                        $roomItem = $this->roomService->getRoomItem($options['roomContextId']);

                        if ('portal' == $currentPortalItem->getProjectRoomCreationStatus()) {
                            $types['project'] = 'project';
                        } elseif (CS_COMMUNITY_TYPE == $roomItem?->getType()) {
                            $types['project'] = 'project';
                        }

                        if ('all' == $currentPortalItem->getCommunityRoomCreationStatus()) {
                            $types['community'] = 'community';
                        }
                    }

                    return $types;
                }),
                'label' => 'context type',
                'expanded' => true,
                'mapped' => false,
                'translation_domain' => 'room',
                'data' => $options['forceType'] ?: null,
                'disabled' => $options['forceType'] ?: false,
            ])

            ->addDependent('type_sub', 'type_select', function (DependentField $field, ?string $type) use ($options) {
                if (!$type && !$options['forceType']) return;

                if ($options['forceType'] === 'project' || $type === 'project') {
                    $field->add(ProjectType::class, [
                        'label' => false,
                        'inherit_data' => true,
                    ]);
                } else if ($type === 'community') {
                    $field->add(CommunityType::class, [
                        'label' => false,
                        'inherit_data' => true,
                    ]);
                }
            })
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
            ->setDefaults([
                'translation_domain' => 'project',
                'data_class' => RoomData::class,
                'roomContextId' => null, // If this form is used in a community room context, set the id
                'forceType' => null,
            ])
            ->setAllowedTypes('roomContextId', ['null', 'int'])
            ->setAllowedTypes('forceType', ['null', 'string'])
        ;
    }
}
