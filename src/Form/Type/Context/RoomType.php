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
use App\Services\RoomCategoriesService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class RoomType extends AbstractType
{
    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly RoomCategoriesService $roomCategoriesService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();
        $currentPortalItem = $legacyEnvironment->getCurrentPortalItem();

        $builder
            ->add('title', TextType::class)
            ->add('language', ChoiceType::class, [
                'placeholder' => false,
                'choices' => [
                    'User preferences' => 'user',
                    'German' => 'de',
                    'English' => 'en',
                ],
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'help' => 'Room language tip',
                'translation_domain' => 'settings',
                'choice_translation_domain' => 'settings',
            ])
            ->add('roomDescription', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => 'Room description...',
                ],
                'required' => false,
                'translation_domain' => 'room',
            ])
        ;

        $roomCategories = [];
        foreach ($this->roomCategoriesService->getListRoomCategories($currentPortalItem->getItemId()) as $roomCategory) {
            $roomCategories[$roomCategory->getTitle()] = $roomCategory->getId();
        }

        $linkRoomCategoriesMandatory = $currentPortalItem->isTagMandatory() && count($roomCategories) > 0;
        $constraints = [];
        if ($linkRoomCategoriesMandatory) {
            $constraints[] = new Count(['min' => 1, 'minMessage' => 'Please select at least one category']);
        }

        if (!empty($roomCategories)) {
            $builder->add('categories', ChoiceType::class, [
                'placeholder' => false,
                'choices' => $roomCategories,
                'label' => 'Room categories',
                'required' => $linkRoomCategoriesMandatory,
                'expanded' => true,
                'multiple' => true,
                'translation_domain' => 'portal',
                'constraints' => $constraints,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inherit_data' => true,
        ]);
    }
}
