<?php


namespace App\Form\Type;


use App\Model\SearchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type as Types;

class TodoStatusFilterType extends AbstractType
{

    private $translator;

    /**
     * TodoStatusFilterType constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var SearchData $searchData */
        $searchData = $builder->getData();

        $translationDomain = 'search';

        $builder->add('selectedStatus', Types\ChoiceType::class, [
        'attr' => [
            'onchange' => 'this.form.submit()',
        ],
        'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
            $translatedTitleAny = $this->translator->trans('any', [], 'form');
            return array_merge([$translatedTitleAny => 0], $this->buildRubricsChoices($searchData->getStatuses()));
        }),
        'label' => 'todo status',
        'translation_domain' => 'todo',
        'required' => false,
        'placeholder' => false,
    ]);
    }

    /**
     * Builds the array of choices for the rubric filter field.
     *
     * @param array|null $statuses associative array of rubrics (key: rubric name, value: count)
     */
    private function buildRubricsChoices($statuses): array
    {
        if (!isset($statuses) || empty($statuses)) {
            return [];
        }

        $choices = [];
        foreach ($statuses as $code => $count) {
            switch ($code) {
                case 1:
                    // pending
                    $translatedTitle = $this->translator->trans('pending',[],'todo');  //'Pending';
                    break;
                case 2:
                    // in progress
                    $translatedTitle = $this->translator->trans('in progress', [], 'todo'); //'In progress';
                    break;
                case 3:
                    // done
                    $translatedTitle = $this->translator->trans('done',[],'todo'); //'done';
                    break;
                default:
                    $translatedTitle = $code;
            }

            $status = $translatedTitle . " (" . $count . ")";
            $choices[$status] = $code;
        }

        return $choices;
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
}