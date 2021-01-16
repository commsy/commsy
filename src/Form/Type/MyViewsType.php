<?php
namespace App\Form\Type;

use App\Entity\SavedSearch;
use App\Model\SearchData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MyViewsType extends AbstractType
{
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
                    'onchange' => 'this.form.submit()',
                ],
                'class' => SavedSearch::class,
                'choices' => $searchData->getSavedSearches(),
                'choice_label' => 'title',
                'label' => false,
                'required' => false,
                'placeholder' => 'new view',
            ])
            ->add('create', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary uk-margin-small-left',
                ],
                'label' => 'create',
                'validation_groups' => false,
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
                'translation_domain' => 'dashboard',
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
        return 'myviews';
    }
}