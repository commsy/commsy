<?php
namespace App\Filter;

use App\Entity\SavedSearch;
use App\EventSubscriber\ChosenRubricSubscriber;
use App\Form\Type\Custom\Select2ChoiceType;
use App\Model\SearchData;
use App\Search\SearchManager;
use App\Utils\ReaderService;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SearchFilterType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    private $searchManager;

    public function __construct(TranslatorInterface $translator, SearchManager $searchManager)
    {
        $this->translator = $translator;
        $this->searchManager = $searchManager;
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
        /** @var SearchData $searchData */
        $searchData = $builder->getData();

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

            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
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

            ->add('help', TextType::class, [
                'attr' => [
                    'class' => 'uk-text-warning',
                    'style' => 'display:none; background:none; border:none',
                ],
                'label' => false,
                'data' => $this->translator->trans('Apply filters', [], 'form'),
                'required' => false,
                'disabled' => true,
                'mapped' => false,
            ])
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
            /**
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
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Appearing in',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => false,
            ])
            ->add('selectedCreator', Select2ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildTermChoices($searchData->getCreators()));
                }),
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Creator',
                'required' => false,
            ])
            ->add('selectedContext', Select2ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('All my rooms', [], 'search');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildTermChoices($searchData->getContexts()));
                }),
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Contexts',
                'required' => false,
            ])
            ->add('creation_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Created from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label'  => 'from',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
                'right_date_options' => [
                    'label'  => 'until',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
            ])
            ->add('modification_date_range', Filters\DateRangeFilterType::class, [
                'attr' => [
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'autocomplete' => 'off',
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Last modified from/until',
                'required' => false,
                // NOTE: while the left/right date labels won't display, specifying them helps with proper formatting
                'left_date_options' => [
                    'label'  => 'from',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
                'right_date_options' => [
                    'label'  => 'until',
                    'input'  => 'datetime',
                    'widget' => 'single_text',
                    'format' => 'dd.MM.yyyy',
                ],
            ])
            ->add('selectedRubric', Types\ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildRubricsChoices($searchData->getRubrics()));
                }),
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Rubric',
                'required' => false,
                'placeholder' => false,
            ])
            ->add('selectedReadStatus', Types\ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('any', [], 'form') => 'all',
                    'New' => ReaderService::READ_STATUS_NEW,
                    'Modified' => ReaderService::READ_STATUS_CHANGED,
                    'Unread' => ReaderService::READ_STATUS_UNREAD,
                    'Read' => ReaderService::READ_STATUS_SEEN,
                ],
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Read status',
                'required' => false,
                'placeholder' => false,
            ])
            ->addEventSubscriber(new ChosenRubricSubscriber($this->translator))
            ->add('selectedHashtags', Select2ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    return $this->buildTermChoices($searchData->getHashtags());
                }),
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Hashtags',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('selectedCategories', Select2ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    return $this->buildTermChoices($searchData->getCategories());
                }),
                'attr' => [
                    'onchange' => "document.getElementById('search_filter_help').style.display = 'block'",
                ],
                'label' => 'Categories',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
            ]);
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
        return 'search_filter';
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['contextId'])
            ->setDefaults([
                'csrf_protection'    => false,
                'validation_groups'  => ['filtering', 'save'], // avoid NotBlank() constraint-related message
                'method'             => 'get',
                'translation_domain' => 'search',
            ]);
    }

    /**
     * Builds the array of choices for the rubric filter field.
     *
     * @param array|null $rubrics associative array of rubrics (key: rubric name, value: count)
     */
    private function buildRubricsChoices($rubrics): array
    {
        if (!isset($rubrics) || empty($rubrics)) {
            return [];
        }

        $choices = [];
        foreach ($rubrics as $name => $count) {
            $translatedTitle = $this->translator->transChoice(ucfirst($name), 1, [], 'rubric');
            if ($name === "label") {
                $translatedTitle = $this->translator->trans("Groups, Topics and Institutions", [], 'search');
            }
            $rubric = $translatedTitle . " (" . $count . ")";
            $choices[$rubric] = $name;
        }

        return $choices;
    }

    /**
     * Builds the array of choices for the creators/hashtags/categories filter fields.
     *
     * @param array|null $terms associative array of creator/hashtag/category terms (key: term name, value: count)
     */
    private function buildTermChoices($terms): array
    {
        if (!isset($terms) || empty($terms)) {
            return [];
        }

        $choices = [];
        foreach ($terms as $name => $count) {
            $term = $name . " (" . $count . ")";
            $choices[$term] = $name;
        }

        return $choices;
    }
}
