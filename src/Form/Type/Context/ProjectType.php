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

use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use cs_list;
use cs_user_item;
use Generator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectType extends AbstractType
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly TranslatorInterface $translator
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // NOTE: opposed to the Type/ProjectType.php form, the `data` option somehow won't preselect
        // any default template that's defined in `preferredChoices`, maybe due to this form being
        // added as 'type_sub' in `AddContextFieldListener`? However, `empty_data` will work here.
        $builder->add('master_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'placeholder' => false,
                'preferred_choices' => $options['preferredChoices'],
                'required' => false,
                'mapped' => false,
                'label' => 'Template',
                'empty_data' => (!empty($options['preferredChoices'])) ? $options['preferredChoices'][0] : '',
            ]);
        if (!empty($options['times'])) {
            $choices = [$this->translator->trans('Select some options') => ''] + $options['times'];

            $builder->add('time_interval', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $choices,
                'required' => false,
                'mapped' => false,
                'expanded' => false,
                'multiple' => true,
                'label' => $options['timesDisplay'],
                'translation_domain' => 'room',
            ]);
        }
        $builder->add('community_rooms', ChoiceType::class, [
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
            'required' => $options['linkCommunitiesMandantory'],
            'mapped' => false,
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
        ->add('userroom_template', ChoiceType::class, [
            'choices' => $options['templates'],
            'placeholder' => false,
            'required' => false,
            'mapped' => false,
            'label' => 'User room template',
            'translation_domain' => 'settings',
        ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['types', 'templates', 'preferredChoices', 'timesDisplay', 'times', 'linkCommunitiesMandantory', 'roomCategories', 'linkRoomCategoriesMandatory'])
            ->setDefaults(['translation_domain' => 'form'])
        ;
    }
}
