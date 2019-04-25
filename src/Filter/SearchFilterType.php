<?php
namespace App\Filter;

use App\Form\EventListener\AddRubricSearchListener;
use App\Form\Type\Custom\Select2ChoiceType;
use App\Model\SearchData;
use App\Search\FilterConditions\RubricFilterCondition;
use App\Search\SearchManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class SearchFilterType extends AbstractType
{
    private $searchManager;

    public function __construct(SearchManager $searchManager)
    {
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
        $searchParams = $options['parameters']->get('search_filter');

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
                'translation_domain' => 'search',
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
            ])
            ->add('selectedCreators', Select2ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    // TODO: Translation needed!
                    return array_combine($searchData->getCreators() ?: [], $searchData->getCreators() ?: []);
                }),
                'expanded' => false,
                'multiple' => true,
            ])
            ->add('creation_date_range', Types\TextType::class)
            ->add('modification_date_range', Types\TextType::class)
            ->add('selectedRubric', Types\ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    // TODO: Translation needed!
                    return array_merge(['all' => 'all'], array_combine($searchData->getRubrics() ?: [], $searchData->getRubrics() ?: []));
                }),
            ])
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
            ->setRequired(['contextId', 'parameters'])
            ->setDefaults([
            'csrf_protection'   => false,
            'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
            'method'            => 'get',
        ]);
    }
}