<?php
namespace App\Form\Type;

use App\Entity\SavedSearch;
use App\Model\SearchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface;

class ManageMyViewsType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
            ->add('selectedSavedSearchId', Types\ChoiceType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choice_loader' => new CallbackChoiceLoader(function() use ($searchData) {
                    $translatedTitleNew = $this->translator->trans('New view', [], 'search');
                    return array_merge([$translatedTitleNew => 0], $this->buildSavedSearchChoices($searchData->getSavedSearches()));
                }),
                'label' => 'My view',
                'required' => false,
                'placeholder' => false,
            ])
            ->add('selectedSavedSearchTitle', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Title',
                'required' => true,

            ])
            ->add('save', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([])
            ->setDefaults([
                'csrf_protection'    => false,
                'method'             => 'get',
                'translation_domain' => 'search',
            ]);
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
        return 'manage_my_views';
    }

    /**
     * Builds the array of choices for the dropdown of saved searches (aka "views").
     *
     * @param SavedSearch[]|null $savedSearches array of SavedSearch objects
     */
    private function buildSavedSearchChoices(?array $savedSearches): array
    {
        if (empty($savedSearches)) {
            return [];
        }

        $choices = [];
        foreach ($savedSearches as $savedSearch) {
            $choices[$savedSearch->getTitle()] = $savedSearch->getId();
        }

        return $choices;
    }
}
