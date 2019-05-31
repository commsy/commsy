<?php
namespace App\Filter;

use App\Form\EventListener\AddRubricSearchListener;
use App\Form\Type\Custom\Select2ChoiceType;
use App\Model\SearchData;
use App\Search\SearchManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Translation\TranslatorInterface;

class SearchFilterType extends AbstractType
{
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
            /**
             * Since this form uses the same data class as the global search form, it is important to keep the field
             * name of the search query phrase identical
             */
            ->add('phrase', Types\HiddenType::class, [
                'label' => false,
            ])
            ->add('all_rooms', Filters\CheckboxFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'label' => 'Search in all my rooms',
                'required' => false,
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
            ])
            ->add('selectedCreator', Select2ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildCreatorChoices($searchData->getCreators()));
                }),
                'label' => 'Creator',
                'required' => false,
            ])
            // TODO: for each of the date range form options, provide two date fields with date pickers to describe a date range?
            ->add('creation_date_range', Types\TextType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'required' => false,
            ])
            ->add('modification_date_range', Types\TextType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'required' => false,
            ])
            ->add('selectedRubric', Types\ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleAny = $this->translator->trans('any', [], 'form');
                    return array_merge([$translatedTitleAny => 'all'], $this->buildRubricsChoices($searchData->getRubrics()));
                }),
                'label' => 'Rubric',
                'required' => false,
                'placeholder' => false,
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
                'validation_groups'  => array('filtering'), // avoid NotBlank() constraint-related message
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
            $rubric = $translatedTitle . " (" . $count . ")";
            $choices[$rubric] = $name;
        }

        return $choices;
    }

    /**
     * Builds the array of choices for the creators filter field.
     *
     * @param array|null $creators associative array of creators (key: creator name, value: count)
     */
    private function buildCreatorChoices($creators): array
    {
        if (!isset($creators) || empty($creators)) {
            return [];
        }

        $choices = [];
        foreach ($creators as $name => $count) {
            $creator = $name . " (" . $count . ")";
            $choices[$creator] = $name;
        }

        return $choices;
    }
}