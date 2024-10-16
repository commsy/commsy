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

namespace App\Filter;

use App\Entity\SavedSearch;
use App\Enum\ReaderStatus;
use App\EventSubscriber\ChosenRubricSubscriber;
use App\Model\SearchData;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchFilterType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
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
        /** @var SearchData $searchData */
        $searchData = $builder->getData();

        if (false === $options['userIsReallyGuest']) {
            $builder
                ->add('selectedSavedSearch', EntityType::class, [
                    'attr' => [
                        'onchange' => "document.getElementById('search_filter_load').click()",
                    ],
                    'class' => SavedSearch::class,
                    'choices' => $searchData->getSavedSearches() ?? [],
                    'choice_label' => 'title',
                    'label' => 'My view',
                    'required' => false,
                    'placeholder' => 'New view',
                ])
                // due to the validation annotation `@Assert\NotBlank(...)` for `SearchData->selectedSavedSearchTitle`
                // clicking the "Save" button will require a non-empty title (which does not only consist of whitespace)
                ->add('selectedSavedSearchTitle', Types\TextType::class, [
                    'attr' => [
                        'class' => 'cs-form-horizontal-full-width',
                    ],
                    'label' => 'Title',
                    'required' => false,
                ])
                ->add('save', Types\SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                    ],
                    'label' => 'save',
                    'translation_domain' => 'form',
                    'validation_groups' => 'save',
                ])
                ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                    /** @var SearchData $searchData */
                    $searchData = $event->getData();
                    $form = $event->getForm();

                    $selectedSavedSearch = $searchData->getSelectedSavedSearch();
                    if ($selectedSavedSearch) {
                        $form->add('delete', Types\SubmitType::class, [
                            'attr' => [
                                'class' => 'uk-button-danger',
                            ],
                            'label' => 'Delete',
                            'translation_domain' => 'form',
                            'validation_groups' => false,
                        ]);
                    }
                })
            ;
        }

        $builder
            ->add('submit', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button uk-button-primary',
                ],
                'label' => 'Filter',
                'translation_domain' => 'form',
                'validation_groups' => 'false',
            ])
            // the hidden `load` button will be clicked automatically when a saved search is selected from the
            // `selectedSavedSearch` dropdown
            ->add('load', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-hidden',
                ],
                'label' => 'load',
                'translation_domain' => 'form',
                'validation_groups' => 'false',
            ])
            /*
             * Since this form uses the same data class as the global search form, it is important to keep the field
             * name of the search query phrase identical
             */
            ->add('phrase', Types\HiddenType::class, [
                'label' => false,
            ])
            ->add('appears_in', Filters\ChoiceFilterType::class, [
                'choices' => [
                    'Title' => 'title',
                    'Description' => 'description',
//                    'Files' => 'files',
                ],
                'label' => 'Appearing in',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => false,
            ])
            ->add('selectedCreator', Types\ChoiceType::class, [
                'autocomplete' => true,
                'choice_loader' => new CallbackChoiceLoader(function () use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');

                    return array_merge([$translatedTitleAny => 'all'], $this->buildTermChoices($searchData->getCreators()));
                }),
                'label' => 'Creator',
                'required' => false,
            ])
            ->add('selectedContext', Types\ChoiceType::class, [
                'autocomplete' => true,
                'choice_loader' => new CallbackChoiceLoader(function () use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('All my rooms', [], 'search');

                    return array_merge([$translatedTitleAny => 'all'], $this->buildTermChoices($searchData->getContexts()));
                }),
                'label' => 'Contexts',
                'required' => false,
            ])
            ->add('creation_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                ],
                'label' => 'Created from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label' => 'from',
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                    'html5' => false,
                ],
                'right_date_options' => [
                    'label' => 'until',
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                    'html5' => false,
                ],
            ])
            ->add('modification_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                ],
                'label' => 'Last modified from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label' => 'from',
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                    'html5' => false,
                ],
                'right_date_options' => [
                    'label' => 'until',
                    'input' => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                    'html5' => false,
                ],
            ])
            ->add('selectedRubric', Types\ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function () use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');

                    return array_merge([$translatedTitleAny => 'all'], $this->buildRubricsChoices($searchData->getRubrics()));
                }),
                'label' => 'Rubric',
                'required' => false,
                'placeholder' => false,
            ]);

        if (false === $options['userIsReallyGuest']) {
            $builder
                ->add('selectedReadStatus', Types\ChoiceType::class, [
                    'choices' => [
                        $this->translator->trans('any', [], 'form') => 'all',
                        'New' => ReaderStatus::STATUS_NEW,
                        'Modified' => ReaderStatus::STATUS_CHANGED,
                        'Unread' => ReaderStatus::STATUS_UNREAD,
                        'Read' => ReaderStatus::STATUS_SEEN,
                    ],
                    'label' => 'Read status',
                    'required' => false,
                    'placeholder' => false,
                ]);
        }

        $builder
            ->addEventSubscriber(new ChosenRubricSubscriber($this->translator))
            ->add('selectedHashtags', Types\ChoiceType::class, [
                'autocomplete' => true,
                'choice_loader' => new CallbackChoiceLoader(fn () => $this->buildTermChoices($searchData->getHashtags())),
                'label' => 'Hashtags',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('selectedCategories', Types\ChoiceType::class, [
                'autocomplete' => true,
                'choice_loader' => new CallbackChoiceLoader(fn () => $this->buildTermChoices($searchData->getCategories())),
                'label' => 'Categories',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['contextId', 'userIsReallyGuest'])
            ->setDefaults([
                'csrf_protection' => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method' => 'get',
                'translation_domain' => 'search',
            ]);
    }

    /**
     * Builds the array of choices for the rubric filter field.
     *
     * @param array|null $rubrics associative array of rubrics (key: rubric name, value: count)
     */
    private function buildRubricsChoices(?array $rubrics): array
    {
        if (!isset($rubrics) || empty($rubrics)) {
            return [];
        }

        $choices = [];
        foreach ($rubrics as $name => $count) {
            $translatedTitle = $this->translator->trans(ucfirst($name), ['%count%' => 1], 'rubric');
            if ('label' === $name) {
                $translatedTitle = $this->translator->trans('Groups, Topics and Institutions', [], 'search');
            }
            $rubric = $translatedTitle.' ('.$count.')';
            $choices[$rubric] = $name;
        }

        return $choices;
    }

    /**
     * Builds the array of choices for the creators/hashtags/categories filter fields.
     *
     * @param array|null $terms associative array of creator/hashtag/category terms (key: term name, value: count)
     */
    private function buildTermChoices(?array $terms): array
    {
        $choices = [$this->translator->trans('Select some options') => ''];

        $terms ??= [];

        foreach ($terms as $name => $count) {
            $term = $name.' ('.$count.')';
            $choices[$term] = $name;
        }

        return $choices;
    }
}
